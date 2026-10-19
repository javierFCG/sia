<?php
// estudiante_tareas.php - Ver y entregar tareas
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

// Obtener tareas con información de entregas
$sql = "SELECT t.*, m.nombre as materia_nombre, m.id as materia_id
        FROM tareas t
        JOIN materias m ON t.materia_id = m.id
        ORDER BY t.fecha_limite ASC";
$tareas = $conn->query($sql);

$tareas_data = [];
if ($tareas && $tareas->num_rows > 0) {
    while ($row = $tareas->fetch_assoc()) {
        // Verificar si el estudiante ya entregó
        $check = $conn->prepare("SELECT * FROM entregas_tareas WHERE id_tarea = ? AND id_estudiante = ?");
        $check->bind_param("ii", $row['id'], $user_id);
        $check->execute();
        $entrega = $check->get_result()->fetch_assoc();
        $check->close();
        
        $row['entrego'] = $entrega ? true : false;
        $row['calificacion'] = $entrega ? $entrega['calificacion'] : null;
        $row['comentario'] = $entrega ? $entrega['comentario_docente'] : null;
        $row['archivo_entrega'] = $entrega ? $entrega['ruta_archivo'] : null;
        
        $tareas_data[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Tareas - ITSZ</title>
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

        .container { max-width: 1100px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }

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

        .badge-pendiente {
            background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; display: inline-block;
        }
        .badge-calificado {
            background: #d4edda; color: #155724; padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; display: inline-block;
        }
        .badge-entregado {
            background: #cce5ff; color: #004085; padding: 4px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; display: inline-block;
        }
        .nota-final {
            font-weight: 700; font-size: 18px; color: var(--azul);
        }
        .nota-final .aprobado { color: #27ae60; }
        .nota-final .reprobado { color: var(--rojo); }

        .comentario-docente {
            background: #f8f5f0; border-left: 4px solid var(--azul); padding: 8px 12px;
            margin-top: 5px; font-size: 13px; color: #555; border-radius: var(--radius);
        }

        .btn-subir {
            padding: 8px 18px; background: var(--dorado); color: var(--blanco);
            border: none; border-radius: var(--radius); font-weight: 600; font-size: 13px;
            cursor: pointer; text-decoration: none; display: inline-block;
            transition: all 0.3s;
        }
        .btn-subir:hover { background: #c78a2a; transform: translateY(-2px); }

        .btn-ver-archivo {
            padding: 5px 12px; background: var(--azul); color: var(--blanco);
            border: none; border-radius: var(--radius); font-weight: 600; font-size: 12px;
            cursor: pointer; text-decoration: none; display: inline-block;
            transition: all 0.3s;
        }
        .btn-ver-archivo:hover { background: var(--guinda); transform: translateY(-2px); }

        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius);
            box-shadow: var(--shadow); border-left: 5px solid var(--gris-oscuro);
        }
        .empty-state i { font-size: 70px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
        .empty-state h3 { color: var(--azul); font-size: 22px; margin-bottom: 10px; }
        .empty-state p { color: var(--gris-oscuro); font-size: 15px; }

        .nota-info {
            margin-top: 15px; text-align: center; font-size: 13px; color: var(--gris-oscuro);
            background: #f8f5f0; padding: 12px; border-radius: var(--radius);
        }
        .nota-info i { color: var(--azul); }

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
            .btn-subir, .btn-ver-archivo { display: block; text-align: center; width: 100%; }
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
            <h1><i class='bx bxs-task'></i> Mis Tareas</h1>
            <p>Estudiante: <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></p>
        </div>
        <a href="estudiante_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <?php if (count($tareas_data) > 0): ?>
        <div class="table-container">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Título</th>
                            <th>Descripción</th>
                            <th>Fecha Límite</th>
                            <th style="text-align:center;">Estado / Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tareas_data as $t): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($t['materia_nombre'] ?? 'Sin materia'); ?></strong></td>
                                <td><?php echo htmlspecialchars($t['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($t['descripcion']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($t['fecha_limite'])); ?></td>
                                <td style="min-width: 150px;">
                                    <?php if (!$t['entrego']): ?>
                                        <!-- No ha entregado -->
                                        <a href="subir_tarea.php?id_tarea=<?php echo $t['id']; ?>" class="btn-subir">
                                            <i class='bx bxs-upload'></i> Subir
                                        </a>
                                    <?php elseif ($t['entrego'] && $t['calificacion'] === null): ?>
                                        <!-- Entregó, esperando calificación -->
                                        <span class="badge-pendiente">
                                            <i class='bx bxs-hourglass'></i> En espera
                                        </span>
                                        <?php if ($t['archivo_entrega']): ?>
                                            <div style="margin-top: 5px;">
                                                <a href="<?php echo $t['archivo_entrega']; ?>" target="_blank" class="btn-ver-archivo">
                                                    <i class='bx bxs-file'></i> Ver archivo
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($t['entrego'] && $t['calificacion'] !== null): ?>
                                        <!-- Entregó y calificada -->
                                        <div>
                                            <span class="badge-calificado">
                                                <i class='bx bxs-check-circle'></i> Calificada
                                            </span>
                                            <div class="nota-final">
                                                Calificación: 
                                                <span class="<?php echo $t['calificacion'] >= 6 ? 'aprobado' : 'reprobado'; ?>">
                                                    <?php echo number_format($t['calificacion'], 1); ?>
                                                </span>
                                            </div>
                                            <?php if ($t['comentario']): ?>
                                                <div class="comentario-docente">
                                                    <strong>💬 Comentario del docente:</strong><br>
                                                    <?php echo nl2br(htmlspecialchars($t['comentario'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($t['archivo_entrega']): ?>
                                                <div style="margin-top: 5px;">
                                                    <a href="<?php echo $t['archivo_entrega']; ?>" target="_blank" class="btn-ver-archivo">
                                                        <i class='bx bxs-file'></i> Ver archivo
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="nota-info">
            <i class='bx bx-info-circle'></i> 
            <strong>Nota:</strong> Las tareas calificadas mostrarán la nota y el comentario del docente.
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-inbox'></i>
            <h3>No hay tareas publicadas</h3>
            <p>Los docentes aún no han publicado tareas para tus materias.</p>
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