<?php
// docente_entregas.php - Ver entregas de tareas
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Docente') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['tarea_id']) || !is_numeric($_GET['tarea_id'])) {
    die("ID de tarea no válido.");
}

$tarea_id = (int)$_GET['tarea_id'];
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener info de la tarea
$stmt = $conn->prepare("SELECT t.*, m.nombre as materia FROM tareas t JOIN materias m ON t.materia_id = m.id WHERE t.id = ?");
$stmt->bind_param("i", $tarea_id);
$stmt->execute();
$tarea = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tarea) { die("Tarea no encontrada."); }

$mensaje = "";
$mensaje_tipo = "";

// Procesar calificación de entrega
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calificar'])) {
    $entrega_id = (int)$_POST['entrega_id'];
    $calificacion = (float)$_POST['calificacion'];
    
    if ($calificacion < 0 || $calificacion > 10) {
        $mensaje = "❌ La calificación debe estar entre 0 y 10";
        $mensaje_tipo = "error";
    } else {
        $stmt = $conn->prepare("UPDATE entregas_tareas SET calificacion = ? WHERE id = ?");
        $stmt->bind_param("di", $calificacion, $entrega_id);
        if ($stmt->execute()) {
            $mensaje = "✅ Calificación guardada correctamente";
            $mensaje_tipo = "success";
        } else {
            $mensaje = "❌ Error al guardar la calificación";
            $mensaje_tipo = "error";
        }
        $stmt->close();
    }
}

// Obtener entregas
$sql = "SELECT e.*, u.Nombre as estudiante 
        FROM entregas_tareas e 
        JOIN usuarios u ON e.id_estudiante = u.Id 
        WHERE e.id_tarea = ? 
        ORDER BY e.fecha_entrega DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tarea_id);
$stmt->execute();
$entregas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Entregas - ITSZ</title>
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

        .mensaje { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .entrega-card {
            background: white; border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow);
            margin-bottom: 15px; border-left: 5px solid var(--azul); transition: all 0.3s;
        }
        .entrega-card:hover { box-shadow: var(--shadow-hover); transform: translateX(5px); }
        .entrega-card .estudiante { font-size: 18px; font-weight: 700; color: var(--azul); }
        .entrega-card .estudiante i { color: var(--guinda); }
        .entrega-card .info {
            font-size: 13px; color: var(--gris-oscuro); margin: 5px 0;
        }
        .entrega-card .info i { color: var(--azul); margin-right: 3px; }
        .entrega-card .archivo {
            margin: 10px 0;
        }
        .btn-ver-archivo {
            padding: 6px 16px; background: var(--azul); color: var(--blanco);
            border: none; border-radius: var(--radius); font-weight: 600; font-size: 13px;
            cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s;
        }
        .btn-ver-archivo:hover { background: var(--guinda); transform: translateY(-2px); }
        .btn-ver-archivo i { margin-right: 5px; }

        .form-calificar {
            display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-top: 10px;
            padding-top: 15px; border-top: 1px solid var(--gris);
        }
        .form-calificar .input-calif {
            width: 80px; padding: 8px; border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius); text-align: center; font-size: 14px;
            background: white; transition: all 0.3s;
        }
        .form-calificar .input-calif:focus {
            border-color: var(--azul); outline: none; box-shadow: 0 0 0 3px rgba(27,57,106,0.1);
        }
        .form-calificar .btn-calificar {
            padding: 8px 20px; background: #27ae60; color: var(--blanco);
            border: none; border-radius: var(--radius); font-weight: 600; font-size: 14px;
            cursor: pointer; transition: all 0.3s;
        }
        .form-calificar .btn-calificar:hover { background: #219a52; transform: translateY(-2px); }

        .badge-calificado {
            background: #d4edda; color: #155724; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; display: inline-block;
        }
        .badge-pendiente {
            background: #fff3cd; color: #856404; padding: 3px 12px; border-radius: 20px;
            font-size: 12px; font-weight: 600; display: inline-block;
        }
        .comentario-docente {
            background: #f8f5f0; border-left: 4px solid var(--azul);
            padding: 8px 12px; margin-top: 10px; font-size: 13px; color: #555;
            border-radius: var(--radius);
        }

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
            .form-calificar { flex-direction: column; align-items: stretch; }
            .form-calificar .btn-calificar { text-align: center; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .entrega-card { padding: 15px; }
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
            <h1><i class='bx bxs-folder-open'></i> Entregas</h1>
            <div class="sub">
                Tarea: <strong><?php echo htmlspecialchars($tarea['titulo']); ?></strong> | 
                Materia: <strong><?php echo htmlspecialchars($tarea['materia']); ?></strong>
            </div>
        </div>
        <a href="docente_tareas.php?materia_id=<?php echo $tarea['materia_id']; ?>" class="btn-back">
            <i class='bx bx-arrow-back'></i> Volver
        </a>
    </div>

    <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if (count($entregas) > 0): ?>
        <?php foreach ($entregas as $e): ?>
            <div class="entrega-card">
                <div class="estudiante">
                    <i class='bx bxs-user-circle'></i> <?php echo htmlspecialchars($e['estudiante']); ?>
                </div>
                <div class="info">
                    <i class='bx bxs-calendar'></i> Fecha de entrega: <?php echo date('d/m/Y H:i', strtotime($e['fecha_entrega'])); ?>
                </div>
                <?php if ($e['archivo']): ?>
                    <div class="archivo">
                        <a href="<?php echo $e['ruta_archivo']; ?>" target="_blank" class="btn-ver-archivo">
                            <i class='bx bxs-download'></i> Ver archivo
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if ($e['calificacion'] !== null): ?>
                    <div style="margin-top: 10px;">
                        <span class="badge-calificado">
                            <i class='bx bxs-check-circle'></i> Calificación: <?php echo number_format($e['calificacion'], 1); ?>
                        </span>
                    </div>
                    <?php if ($e['comentario_docente']): ?>
                        <div class="comentario-docente">
                            <strong><i class='bx bxs-message'></i> Comentario del docente:</strong><br>
                            <?php echo nl2br(htmlspecialchars($e['comentario_docente'])); ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div style="margin-top: 10px;">
                        <span class="badge-pendiente">
                            <i class='bx bxs-hourglass'></i> Pendiente de calificar
                        </span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-calificar">
                    <input type="hidden" name="entrega_id" value="<?php echo $e['id']; ?>">
                    <label style="font-weight: 600; color: var(--azul);">
                        <i class='bx bxs-edit'></i> Calificación (0-10):
                    </label>
                    <input type="number" name="calificacion" min="0" max="10" step="0.1" 
                           value="<?php echo $e['calificacion'] !== null ? $e['calificacion'] : ''; ?>" 
                           class="input-calif" required>
                    <button type="submit" name="calificar" class="btn-calificar">
                        <i class='bx bxs-save'></i> Guardar
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-folder-x'></i>
            <h3>No hay entregas para esta tarea</h3>
            <p>Los estudiantes aún no han enviado sus trabajos.</p>
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