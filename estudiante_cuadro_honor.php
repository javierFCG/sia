<?php
// estudiante_cuadro_honor.php - Ver cuadro de honor para estudiantes
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener mi posición
$sql_mi = "SELECT u.Nombre, AVG(idet.calificacion) as promedio,
           (SELECT COUNT(DISTINCT u2.Id) 
            FROM inscripciones_detalle idet2 
            JOIN inscripciones i2 ON idet2.inscripcion_id = i2.id 
            JOIN usuarios u2 ON i2.usuario_id = u2.Id 
            WHERE idet2.calificacion IS NOT NULL 
            GROUP BY u2.Id 
            HAVING AVG(idet2.calificacion) > AVG(idet.calificacion)) + 1 as posicion
           FROM inscripciones_detalle idet
           JOIN inscripciones i ON idet.inscripcion_id = i.id
           JOIN usuarios u ON i.usuario_id = u.Id
           WHERE u.Id = ?
           AND idet.calificacion IS NOT NULL";
$stmt = $conn->prepare($sql_mi);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$mi_posicion = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Top 10
$sql_top = "SELECT u.Nombre, AVG(idet.calificacion) as promedio
            FROM inscripciones_detalle idet
            JOIN inscripciones i ON idet.inscripcion_id = i.id
            JOIN usuarios u ON i.usuario_id = u.Id
            WHERE idet.calificacion IS NOT NULL
            GROUP BY u.Id
            HAVING COUNT(idet.id) >= 3
            ORDER BY promedio DESC
            LIMIT 10";
$top_10 = $conn->query($sql_top)->fetch_all(MYSQLI_ASSOC);
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

        .mi-posicion {
            background: linear-gradient(135deg, #f0f4ff 0%, #e8eeff 100%);
            border: 2px solid var(--azul);
            border-radius: var(--radius);
            padding: 25px;
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s;
        }
        .mi-posicion:hover { box-shadow: var(--shadow-hover); transform: translateY(-3px); }
        .mi-posicion .pos { font-size: 48px; font-weight: 700; color: var(--azul); }
        .mi-posicion .prom { font-size: 28px; font-weight: 700; color: var(--azul); }
        .mi-posicion .label { font-size: 14px; color: var(--gris-oscuro); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; }

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

        .podium-oro { background: linear-gradient(135deg, #fff8e1, #ffd54f); border: 2px solid #f9a825; }
        .podium-plata { background: linear-gradient(135deg, #f5f5f5, #bdbdbd); border: 2px solid #9e9e9e; }
        .podium-bronce { background: linear-gradient(135deg, #fff3e0, #ffab91); border: 2px solid #e65100; }

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
            <a href="estudiante_panel.php"><i class='bx bxs-graduation'></i> Panel</a>
            <a href="mensajes.php"><i class='bx bxs-envelope'></i> Mensajes</a>
            <a href="logout.php" class="btn-logout"><i class='bx bx-log-out'></i> Salir</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class='bx bxs-trophy'></i> Cuadro de Honor</h1>
            <p>Los mejores promedios del sistema</p>
        </div>
        <a href="estudiante_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <!-- Mi posición -->
    <?php if ($mi_posicion && $mi_posicion['promedio']): ?>
        <div class="mi-posicion">
            <div class="label"><i class='bx bxs-user'></i> Tu posición en el cuadro de honor</div>
            <div class="pos">#<?php echo $mi_posicion['posicion'] ?? '—'; ?></div>
            <div style="margin-top: 5px;">
                <span style="color: var(--gris-oscuro); font-size: 14px;">Promedio:</span>
                <span class="prom"><?php echo number_format($mi_posicion['promedio'], 2); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <?php if (count($top_10) > 0): ?>
        <!-- PODIO TOP 3 -->
        <div class="podium">
            <?php 
            $medallas = ['🥇', '🥈', '🥉'];
            $clases_podium = ['podium-oro', 'podium-plata', 'podium-bronce'];
            for ($i = 0; $i < min(3, count($top_10)); $i++):
                $e = $top_10[$i];
                $clase_promedio = $e['promedio'] >= 9 ? 'promedio-excelente' : ($e['promedio'] >= 8 ? 'promedio-bueno' : '');
            ?>
                <div class="podium-item <?php echo $clases_podium[$i]; ?>">
                    <span class="medalla"><?php echo $medallas[$i]; ?></span>
                    <div class="posicion">#<?php echo $i + 1; ?></div>
                    <div class="nombre"><?php echo htmlspecialchars($e['Nombre']); ?></div>
                    <div class="promedio <?php echo $clase_promedio; ?>"><?php echo number_format($e['promedio'], 2); ?></div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- TABLA TOP 10 -->
        <h3 style="color: var(--azul); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bxs-trophy' style="color: var(--dorado);"></i> Top 10 Mejores Promedios
        </h3>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center;">#</th>
                        <th>Estudiante</th>
                        <th style="text-align:center;">Promedio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($top_10 as $e): 
                        $medalla = '';
                        if ($i == 1) $medalla = '🥇';
                        elseif ($i == 2) $medalla = '🥈';
                        elseif ($i == 3) $medalla = '🥉';
                        $clase = $e['promedio'] >= 9 ? 'promedio-excelente' : ($e['promedio'] >= 8 ? 'promedio-bueno' : '');
                        $destacado = ($i <= 3) ? 'destacado' : '';
                    ?>
                        <tr class="<?php echo $destacado; ?>">
                            <td style="text-align:center;">
                                <span class="medalla-tabla"><?php echo $medalla ?: $i; ?></span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($e['Nombre']); ?></strong></td>
                            <td style="text-align:center; font-weight:700;" class="<?php echo $clase; ?>">
                                <?php echo number_format($e['promedio'], 2); ?>
                            </td>
                        </tr>
                    <?php $i++; endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: right; color: var(--gris-oscuro); font-size: 13px;">
                <i class='bx bxs-trophy'></i> Mostrando <strong><?php echo count($top_10); ?></strong> estudiantes
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