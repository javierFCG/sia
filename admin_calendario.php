<?php
// admin_calendario.php - Gestión de eventos
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

$mensaje = "";
$mensaje_tipo = "";

// Crear evento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_evento'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $tipo = $_POST['tipo'];
    $color = $_POST['color'];
    
    $stmt = $conn->prepare("INSERT INTO eventos (titulo, descripcion, fecha_inicio, fecha_fin, tipo, color) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $titulo, $descripcion, $fecha_inicio, $fecha_fin, $tipo, $color);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Evento creado exitosamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al crear el evento: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

// Eliminar evento
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $stmt = $conn->prepare("DELETE FROM eventos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $mensaje = "🗑️ Evento eliminado correctamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al eliminar el evento";
        $mensaje_tipo = "error";
    }
    $stmt->close();
    header("Location: admin_calendario.php");
    exit();
}

// Obtener eventos
$eventos = $conn->query("SELECT * FROM eventos ORDER BY fecha_inicio DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();

$colores = ['#1B396A', '#620F31', '#D99A32', '#27ae60', '#e74c3c', '#3498db', '#1abc9c', '#f39c12', '#9b59b6'];
$tipos = ['Académico', 'Examen', 'Vacaciones', 'Entrega', 'Otro'];
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
        .btn-crear { background: var(--dorado); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-crear:hover { background: #c78a2a; transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .evento-item {
            background: white; border-radius: var(--radius); padding: 18px 22px; box-shadow: var(--shadow);
            margin-bottom: 15px; border-left: 5px solid var(--azul); transition: all 0.3s;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .evento-item:hover { box-shadow: var(--shadow-hover); transform: translateX(5px); }
        .evento-item .info { flex: 1; }
        .evento-item .info h3 { color: var(--azul); font-size: 18px; margin-bottom: 3px; }
        .evento-item .info .fecha { font-size: 13px; color: var(--gris-oscuro); display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
        .evento-item .info .fecha i { color: var(--guinda); }
        .evento-item .info .desc { color: #555; font-size: 14px; margin-top: 5px; }
        .evento-item .info .tipo {
            display: inline-block; padding: 2px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 5px;
        }
        .btn-eliminar-evento {
            background: var(--rojo); color: var(--blanco); padding: 8px 14px; border: none; border-radius: var(--radius);
            cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.3s; display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-eliminar-evento:hover { background: #6b080c; transform: translateY(-2px); }

        .evento-actual { border-left-color: #27ae60; background: #f0fff4; }
        .evento-proximo { border-left-color: var(--dorado); background: #fffbf0; }

        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);
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
        .input-group input, .input-group select, .input-group textarea {
            width: 100%; padding: 10px 14px; border: 2px solid var(--gris-oscuro); border-radius: var(--radius);
            font-size: 14px; transition: all 0.3s; box-sizing: border-box; background: var(--gris); color: #333;
        }
        .input-group input:focus, .input-group select:focus, .input-group textarea:focus {
            border-color: var(--azul); outline: none; background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27,57,106,0.1);
        }
        .input-group textarea { resize: vertical; min-height: 60px; }
        .input-group select { appearance: none; cursor: pointer; }

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
            .evento-item { flex-direction: column; align-items: flex-start; }
            .modal-content { padding: 20px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .btn-eliminar-evento { width: 100%; justify-content: center; }
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
        <div><h1><i class='bx bxs-calendar-event'></i> Calendario Académico</h1><p>Gestiona eventos importantes del período académico</p></div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="abrirModal()" class="btn-crear"><i class='bx bxs-plus-circle'></i> Nuevo Evento</button>
            <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if (count($eventos) > 0): ?>
        <?php foreach ($eventos as $e): 
            $hoy = date('Y-m-d H:i:s');
            $clase = '';
            if ($e['fecha_inicio'] <= $hoy && $e['fecha_fin'] >= $hoy) $clase = 'evento-actual';
            elseif ($e['fecha_inicio'] > $hoy) $clase = 'evento-proximo';
        ?>
            <div class="evento-item <?php echo $clase; ?>" style="border-left-color: <?php echo $e['color'] ?? '#1B396A'; ?>;">
                <div class="info">
                    <h3><?php echo htmlspecialchars($e['titulo']); ?></h3>
                    <div class="fecha">
                        <i class='bx bx-calendar'></i> 
                        <?php echo date('d/m/Y H:i', strtotime($e['fecha_inicio'])); ?> - 
                        <?php echo date('d/m/Y H:i', strtotime($e['fecha_fin'])); ?>
                    </div>
                    <?php if ($e['descripcion']): ?>
                        <div class="desc"><?php echo nl2br(htmlspecialchars($e['descripcion'])); ?></div>
                    <?php endif; ?>
                    <span class="tipo" style="background: <?php echo ($e['color'] ?? '#1B396A'); ?>20; color: <?php echo $e['color'] ?? '#1B396A'; ?>;">
                        <?php echo $e['tipo']; ?>
                    </span>
                </div>
                <div>
                    <a href="admin_calendario.php?eliminar=<?php echo $e['id']; ?>" class="btn-eliminar-evento" onclick="return confirm('¿Eliminar este evento?')">
                        <i class='bx bxs-trash'></i> Eliminar
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
        <div style="margin-top: 15px; text-align: right; color: var(--gris-oscuro); font-size: 13px;">
            <i class='bx bxs-calendar'></i> Total: <strong><?php echo count($eventos); ?></strong> eventos programados
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-calendar-x'></i>
            <h3>No hay eventos programados</h3>
            <p>Haz clic en "Nuevo Evento" para comenzar a agregar eventos al calendario académico.</p>
            <button onclick="abrirModal()" class="btn-crear" style="margin-top: 15px; padding: 12px 30px; font-size: 16px;">
                <i class='bx bxs-plus-circle'></i> Agregar Evento
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para crear evento -->
<div class="modal" id="modalEvento">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class='bx bxs-calendar-plus'></i> Nuevo Evento</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="input-group">
                <label>Título del Evento *</label>
                <input type="text" name="titulo" required placeholder="Ej: Inicio de clases, Examen final...">
            </div>
            <div class="input-group">
                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Detalles del evento..."></textarea>
            </div>
            <div class="input-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Fecha Inicio *</label>
                    <input type="datetime-local" name="fecha_inicio" required>
                </div>
                <div>
                    <label>Fecha Fin *</label>
                    <input type="datetime-local" name="fecha_fin" required>
                </div>
            </div>
            <div class="input-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Tipo</label>
                    <select name="tipo">
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?php echo $tipo; ?>"><?php echo $tipo; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Color</label>
                    <select name="color">
                        <?php foreach ($colores as $color): ?>
                            <option value="<?php echo $color; ?>" style="background: <?php echo $color; ?>; color: white; padding: 5px;">
                                <?php echo $color; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" name="crear_evento" class="btn-guardar"><i class='bx bxs-save'></i> Guardar</button>
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
        document.getElementById('modalEvento').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        document.getElementById('modalEvento').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('modalEvento').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });
</script>
</body>
</html>