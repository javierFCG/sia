<?php
// docente_tareas.php - Gestión de tareas para docentes
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
$docente_id = $_SESSION['user_id'];
$docente_nombre = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Verificar materia
$stmt = $conn->prepare("SELECT nombre FROM materias WHERE id = ? AND docente_asignado = ?");
$stmt->bind_param("is", $materia_id, $docente_nombre);
$stmt->execute();
$materia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$materia) { die("No tienes permiso para esta materia."); }

$mensaje = "";
$mensaje_tipo = "";

// Crear nueva tarea
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_tarea'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_limite = $_POST['fecha_limite'];
    
    $stmt = $conn->prepare("INSERT INTO tareas (materia_id, docente_id, titulo, descripcion, fecha_limite) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $materia_id, $docente_id, $titulo, $descripcion, $fecha_limite);
    if ($stmt->execute()) {
        $mensaje = "✅ Tarea creada exitosamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al crear la tarea: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

// Obtener tareas
$sql = "SELECT t.*, 
        (SELECT COUNT(*) FROM entregas_tareas WHERE id_tarea = t.id) as entregas_total,
        (SELECT COUNT(*) FROM entregas_tareas WHERE id_tarea = t.id AND calificacion IS NOT NULL) as entregas_calificadas
        FROM tareas t
        WHERE t.materia_id = ?
        ORDER BY t.fecha_publicacion DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_id);
$stmt->execute();
$tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas - ITSZ</title>
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
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }
        .btn-crear { background: var(--dorado); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 14px; }
        .btn-crear:hover { background: #c78a2a; transform: translateY(-2px); }

        .mensaje { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .tarea-item {
            background: white; border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow);
            margin-bottom: 15px; border-left: 5px solid var(--azul); transition: all 0.3s;
        }
        .tarea-item:hover { box-shadow: var(--shadow-hover); transform: translateX(5px); }
        .tarea-item .tarea-header {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .tarea-item .tarea-header h3 { color: var(--azul); font-size: 18px; margin: 0; }
        .tarea-item .meta {
            font-size: 13px; color: var(--gris-oscuro); margin: 5px 0;
            display: flex; flex-wrap: wrap; gap: 15px;
        }
        .tarea-item .meta i { color: var(--guinda); }
        .tarea-item .desc { color: #555; font-size: 14px; margin: 10px 0; }
        .tarea-item .stats {
            display: flex; gap: 15px; font-size: 13px; margin: 10px 0;
        }
        .tarea-item .stats span {
            background: var(--gris); padding: 3px 12px; border-radius: 12px; font-weight: 600;
        }
        .tarea-item .actions { margin-top: 10px; display: flex; gap: 10px; flex-wrap: wrap; }
        .btn-entregas {
            padding: 8px 18px; background: var(--azul); color: var(--blanco); border: none;
            border-radius: var(--radius); font-weight: 600; font-size: 13px; cursor: pointer;
            text-decoration: none; display: inline-block; transition: all 0.3s;
        }
        .btn-entregas:hover { background: var(--guinda); transform: translateY(-2px); }
        .btn-eliminar {
            padding: 8px 18px; background: var(--rojo); color: var(--blanco); border: none;
            border-radius: var(--radius); font-weight: 600; font-size: 13px; cursor: pointer;
            text-decoration: none; display: inline-block; transition: all 0.3s;
        }
        .btn-eliminar:hover { background: #6b080c; transform: translateY(-2px); }

        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius);
            box-shadow: var(--shadow); border-left: 5px solid var(--gris-oscuro);
        }
        .empty-state i { font-size: 60px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
        .empty-state h3 { color: var(--azul); font-size: 20px; margin-bottom: 10px; }
        .empty-state p { color: var(--gris-oscuro); font-size: 15px; }

        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 2000; justify-content: center; align-items: center;
            animation: fadeIn 0.3s ease;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white; padding: 30px; border-radius: var(--radius-lg); max-width: 550px; width: 90%;
            box-shadow: var(--shadow-hover); max-height: 90vh; overflow-y: auto;
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;
            border-bottom: 2px solid var(--gris); padding-bottom: 15px;
        }
        .modal-header h2 { color: var(--azul); font-size: 22px; }
        .modal-header h2 i { color: var(--guinda); }
        .modal-header .close { font-size: 28px; cursor: pointer; color: var(--gris-oscuro); transition: all 0.3s; }
        .modal-header .close:hover { color: var(--rojo); transform: rotate(90deg); }

        .input-group { margin-bottom: 15px; }
        .input-group label { display: block; font-weight: 600; color: var(--azul); margin-bottom: 5px; font-size: 14px; }
        .input-group input, .input-group textarea {
            width: 100%; padding: 10px 14px; border: 2px solid var(--gris-oscuro); border-radius: var(--radius);
            font-size: 14px; transition: all 0.3s; box-sizing: border-box; background: var(--gris); color: #333;
        }
        .input-group input:focus, .input-group textarea:focus {
            border-color: var(--azul); outline: none; background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27,57,106,0.1);
        }
        .input-group textarea { resize: vertical; min-height: 80px; }

        .modal-footer {
            display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;
            padding-top: 15px; border-top: 1px solid var(--gris);
        }
        .btn-cancelar { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-cancelar:hover { background: #b0b0b0; }
        .btn-guardar { background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-guardar:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

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
            .tarea-item .tarea-header { flex-direction: column; align-items: flex-start; }
            .modal-content { padding: 20px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .tarea-item .actions { flex-direction: column; }
            .tarea-item .actions .btn { text-align: center; }
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
            <h1><i class='bx bxs-task'></i> Tareas</h1>
            <div class="sub">Materia: <strong><?php echo htmlspecialchars($materia['nombre']); ?></strong></div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="abrirModal()" class="btn-crear"><i class='bx bxs-plus-circle'></i> Nueva Tarea</button>
            <a href="docente_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if (count($tareas) > 0): ?>
        <?php foreach ($tareas as $t): ?>
            <div class="tarea-item">
                <div class="tarea-header">
                    <h3><?php echo htmlspecialchars($t['titulo']); ?></h3>
                    <span style="font-size: 13px; color: var(--gris-oscuro);">
                        <i class='bx bxs-calendar'></i> Publicada: <?php echo date('d/m/Y H:i', strtotime($t['fecha_publicacion'])); ?>
                    </span>
                </div>
                <div class="meta">
                    <span><i class='bx bxs-time'></i> Límite: <?php echo date('d/m/Y H:i', strtotime($t['fecha_limite'])); ?></span>
                </div>
                <div class="desc"><?php echo nl2br(htmlspecialchars($t['descripcion'])); ?></div>
                <div class="stats">
                    <span><i class='bx bxs-cloud-upload' style="color: var(--azul);"></i> Entregas: <?php echo $t['entregas_total']; ?></span>
                    <span><i class='bx bxs-check-square' style="color: #27ae60;"></i> Calificadas: <?php echo $t['entregas_calificadas']; ?></span>
                </div>
                <div class="actions">
                    <a href="docente_entregas.php?tarea_id=<?php echo $t['id']; ?>" class="btn-entregas">
                        <i class='bx bxs-folder-open'></i> Ver Entregas
                    </a>
                    <a href="docente_eliminar_tarea.php?id=<?php echo $t['id']; ?>&materia_id=<?php echo $materia_id; ?>" 
                       class="btn-eliminar" onclick="return confirm('¿Eliminar esta tarea?')">
                        <i class='bx bxs-trash'></i> Eliminar
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-task-x'></i>
            <h3>No hay tareas creadas</h3>
            <p>Haz clic en "Nueva Tarea" para comenzar a publicar tareas.</p>
            <button onclick="abrirModal()" class="btn-crear" style="margin-top: 15px; padding: 12px 30px; font-size: 16px;">
                <i class='bx bxs-plus-circle'></i> Crear primera tarea
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para crear tarea -->
<div class="modal" id="modalTarea">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class='bx bxs-task'></i> Nueva Tarea</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="input-group">
                <label>Título de la Tarea *</label>
                <input type="text" name="titulo" required placeholder="Ej: Investigación sobre ...">
            </div>
            <div class="input-group">
                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Describe la tarea en detalle..."></textarea>
            </div>
            <div class="input-group">
                <label>Fecha Límite *</label>
                <input type="datetime-local" name="fecha_limite" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" name="crear_tarea" class="btn-guardar"><i class='bx bxs-save'></i> Publicar</button>
            </div>
        </form>
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
    function abrirModal() {
        document.getElementById('modalTarea').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        document.getElementById('modalTarea').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('modalTarea').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });
</script>
</body>
</html>