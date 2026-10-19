<?php
// admin_cuadro_honor.php - Cuadro de honor
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// Obtener top estudiantes por promedio general
$sql = "SELECT u.Nombre, u.Correo, 
        AVG(idet.calificacion) as promedio,
        COUNT(idet.id) as materias,
        SUM(CASE WHEN idet.calificacion >= 70 THEN 1 ELSE 0 END) as aprobadas
        FROM inscripciones_detalle idet
        JOIN inscripciones i ON idet.inscripcion_id = i.id
        JOIN usuarios u ON i.usuario_id = u.Id
        WHERE idet.calificacion IS NOT NULL
        GROUP BY u.Id
        HAVING COUNT(idet.id) >= 3
        ORDER BY promedio DESC
        LIMIT 20";

$top_estudiantes = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cuadro de Honor - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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

        /* ==============================================
           PODIO - TOP 3
           ============================================== */
        .podium {
            display: flex; justify-content: center; align-items: flex-end; gap: 20px; margin-bottom: 30px;
            padding: 20px 0; flex-wrap: wrap;
        }
        .podium-item {
            text-align: center; padding: 20px 25px; border-radius: var(--radius); transition: all 0.3s;
            min-width: 150px; position: relative;
        }
        .podium-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
        .podium-item .medalla { font-size: 48px; display: block; margin-bottom: 5px; }
        .podium-item .posicion { font-size: 14px; font-weight: 700; color: var(--gris-oscuro); }
        .podium-item .nombre { font-size: 18px; font-weight: 700; color: var(--azul); margin: 5px 0; }
        .podium-item .promedio { font-size: 28px; font-weight: 700; }
        .podium-item .promedio-excelente { color: #27ae60; }
        .podium-item .promedio-bueno { color: var(--dorado); }
        .podium-item .materias { font-size: 13px; color: var(--gris-oscuro); }

        .podium-oro { background: linear-gradient(135deg, #fff8e1, #ffd54f); border: 2px solid #f9a825; }
        .podium-plata { background: linear-gradient(135deg, #f5f5f5, #bdbdbd); border: 2px solid #9e9e9e; }
        .podium-bronce { background: linear-gradient(135deg, #fff3e0, #ffab91); border: 2px solid #e65100; }

        /* ==============================================
           TABLA - RESTO DE ESTUDIANTES
           ============================================== */
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
        .destacado { background: #f8f5f0; }

        .medalla-tabla { font-size: 20px; }
        .promedio-excelente { color: #27ae60; font-weight: 700; }
        .promedio-bueno { color: var(--dorado); font-weight: 700; }

        .badge-aprobadas {
            display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        }
        .badge-aprobadas.alta { background: #d4edda; color: #155724; }
        .badge-aprobadas.media { background: #fff3cd; color: #856404; }
        .badge-aprobadas.baja { background: #f8d7da; color: #721c24; }

        /* ==============================================
           EMPTY STATE
           ============================================== */
        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius);
            box-shadow: var(--shadow); border-left: 5px solid var(--dorado);
        }
        .empty-state i { font-size: 70px; color: var(--dorado); margin-bottom: 15px; display: block; }
        .empty-state h3 { color: var(--azul); font-size: 22px; margin-bottom: 10px; }
        .empty-state p { color: var(--gris-oscuro); font-size: 15px; max-width: 450px; margin: 0 auto; }
        .empty-state .requisitos {
            margin-top: 20px; display: inline-block; background: var(--gris); padding: 15px 25px;
            border-radius: var(--radius); font-size: 14px; color: var(--gris-oscuro);
        }
        .empty-state .requisitos i { font-size: 18px; color: var(--azul); display: inline; margin: 0; }

        /* ==============================================
           FOOTER
           ============================================== */
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
            .podium { gap: 10px; }
            .podium-item { min-width: 100px; padding: 15px; }
            .podium-item .medalla { font-size: 32px; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .podium { flex-direction: column; align-items: center; }
            .podium-item { width: 100%; max-width: 250px; }
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
        <div><h1><i class='bx bxs-trophy'></i> Cuadro de Honor</h1><p>Estudiantes con mejores promedios académicos</p></div>
        <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <?php if (count($top_estudiantes) > 0): ?>
        <!-- PODIO TOP 3 -->
        <div class="podium">
            <?php 
            $medallas = ['🥇', '🥈', '🥉'];
            $clases_podium = ['podium-oro', 'podium-plata', 'podium-bronce'];
            for ($i = 0; $i < min(3, count($top_estudiantes)); $i++):
                $e = $top_estudiantes[$i];
                $clase_promedio = $e['promedio'] >= 9 ? 'promedio-excelente' : ($e['promedio'] >= 8 ? 'promedio-bueno' : '');
            ?>
                <div class="podium-item <?php echo $clases_podium[$i]; ?>">
                    <span class="medalla"><?php echo $medallas[$i]; ?></span>
                    <div class="posicion">#<?php echo $i + 1; ?></div>
                    <div class="nombre"><?php echo htmlspecialchars($e['Nombre']); ?></div>
                    <div class="promedio <?php echo $clase_promedio; ?>"><?php echo number_format($e['promedio'], 2); ?></div>
                    <div class="materias"><?php echo $e['materias']; ?> materias · <?php echo $e['aprobadas']; ?> aprobadas</div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- TABLA CON EL RESTO -->
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center;">#</th>
                        <th>Estudiante</th>
                        <th>Correo</th>
                        <th style="text-align:center;">Promedio</th>
                        <th style="text-align:center;">Materias</th>
                        <th style="text-align:center;">Aprobadas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($top_estudiantes as $e): 
                        $medalla = '';
                        if ($i == 1) $medalla = '🥇';
                        elseif ($i == 2) $medalla = '🥈';
                        elseif ($i == 3) $medalla = '🥉';
                        $clase = $e['promedio'] >= 9 ? 'promedio-excelente' : ($e['promedio'] >= 8 ? 'promedio-bueno' : '');
                        $destacado = ($i <= 3) ? 'destacado' : '';
                        $porcentaje = $e['materias'] > 0 ? round(($e['aprobadas'] / $e['materias']) * 100) : 0;
                        $badge_clase = $porcentaje >= 80 ? 'alta' : ($porcentaje >= 50 ? 'media' : 'baja');
                    ?>
                        <tr class="<?php echo $destacado; ?>">
                            <td style="text-align:center;">
                                <span class="medalla-tabla"><?php echo $medalla ?: $i; ?></span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($e['Nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($e['Correo']); ?></td>
                            <td style="text-align:center; font-weight:700;" class="<?php echo $clase; ?>">
                                <?php echo number_format($e['promedio'], 2); ?>
                            </td>
                            <td style="text-align:center;"><?php echo $e['materias']; ?></td>
                            <td style="text-align:center;">
                                <span class="badge-aprobadas <?php echo $badge_clase; ?>">
                                    <?php echo $e['aprobadas']; ?>/<?php echo $e['materias']; ?>
                                </span>
                            </td>
                        </tr>
                    <?php $i++; endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: right; color: var(--gris-oscuro); font-size: 13px;">
                <i class='bx bxs-trophy'></i> Mostrando <strong><?php echo count($top_estudiantes); ?></strong> estudiantes
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-trophy'></i>
            <h3>No hay suficientes datos para mostrar el cuadro de honor</h3>
            <p>Se necesitan al menos <strong>3 materias calificadas</strong> por estudiante para generar el cuadro de honor.</p>
            <div class="requisitos">
                <i class='bx bx-info-circle'></i> Requisitos: Mínimo 3 materias calificadas por estudiante
            </div>
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