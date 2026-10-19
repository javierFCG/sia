<?php
// estudiante_metas.php - Gestión de metas personales
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

// Crear meta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_meta'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    
    $stmt = $conn->prepare("INSERT INTO metas (estudiante_id, titulo, descripcion, fecha_inicio, fecha_fin) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $titulo, $descripcion, $fecha_inicio, $fecha_fin);
    if ($stmt->execute()) {
        $mensaje = "✅ Meta creada exitosamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al crear la meta: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

// Actualizar estado
if (isset($_GET['estado']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $estado = $_GET['estado'];
    $estados_validos = ['Pendiente', 'En progreso', 'Completada', 'Cancelada'];
    if (in_array($estado, $estados_validos)) {
        $stmt = $conn->prepare("UPDATE metas SET estado = ? WHERE id = ? AND estudiante_id = ?");
        $stmt->bind_param("sii", $estado, $id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: estudiante_metas.php");
    exit();
}

// Eliminar meta
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $stmt = $conn->prepare("DELETE FROM metas WHERE id = ? AND estudiante_id = ?");
    $stmt->bind_param("ii", $_GET['eliminar'], $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: estudiante_metas.php");
    exit();
}

// Obtener metas
$metas = $conn->query("SELECT * FROM metas WHERE estudiante_id = $user_id ORDER BY fecha_fin ASC")->fetch_all(MYSQLI_ASSOC);

// Estadísticas
$total = count($metas);
$completadas = 0;
$en_progreso = 0;
$pendientes = 0;
$canceladas = 0;
foreach ($metas as $m) {
    if ($m['estado'] == 'Completada') $completadas++;
    elseif ($m['estado'] == 'En progreso') $en_progreso++;
    elseif ($m['estado'] == 'Pendiente') $pendientes++;
    elseif ($m['estado'] == 'Cancelada') $canceladas++;
}
$conn->close();

$estados = ['Pendiente', 'En progreso', 'Completada', 'Cancelada'];
$colores_estado = [
    'Pendiente' => '#95a5a6',
    'En progreso' => '#f39c12',
    'Completada' => '#27ae60',
    'Cancelada' => '#e74c3c'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Metas - ITSZ</title>
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
        .btn-crear { background: var(--dorado); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-crear:hover { background: #c78a2a; transform: translateY(-2px); }

        .mensaje { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .stats-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 25px;
        }
        .stat-item {
            text-align: center; padding: 15px; border-radius: var(--radius); background: white; box-shadow: var(--shadow);
            border-top: 4px solid var(--azul); transition: all 0.3s;
        }
        .stat-item:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
        .stat-item .num { font-size: 28px; font-weight: 700; }
        .stat-item .label { font-size: 13px; color: var(--gris-oscuro); margin-top: 3px; }
        .stat-item.total .num { color: var(--azul); }
        .stat-item.completadas .num { color: #27ae60; }
        .stat-item.progreso .num { color: #f39c12; }
        .stat-item.pendientes .num { color: #95a5a6; }
        .stat-item.canceladas .num { color: #e74c3c; }

        .meta-item {
            background: white; border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow);
            margin-bottom: 15px; border-left: 5px solid var(--azul); transition: all 0.3s;
        }
        .meta-item:hover { box-shadow: var(--shadow-hover); transform: translateX(5px); }
        .meta-item .header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
        .meta-item .header h3 { color: var(--azul); font-size: 18px; margin: 0; }
        .meta-item .fechas { font-size: 13px; color: var(--gris-oscuro); margin: 5px 0; }
        .meta-item .fechas i { color: var(--guinda); }
        .meta-item .desc { color: #555; margin: 10px 0; }
        .meta-item .acciones { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 5px; }
        .btn-estado {
            padding: 5px 14px; border: none; border-radius: var(--radius); font-size: 12px; font-weight: 600; cursor: pointer; color: white; transition: all 0.3s;
        }
        .btn-estado:hover { transform: translateY(-2px); }
        .btn-estado.pendiente { background: #95a5a6; }
        .btn-estado.progreso { background: #f39c12; }
        .btn-estado.completada { background: #27ae60; }
        .btn-estado.cancelada { background: #e74c3c; }
        .btn-eliminar-meta {
            padding: 5px 14px; background: var(--rojo); color: white; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; font-size: 12px; transition: all 0.3s; text-decoration: none; display: inline-block;
        }
        .btn-eliminar-meta:hover { background: #6b080c; transform: translateY(-2px); }

        .badge-estado {
            padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;
        }
        .badge-estado.Pendiente { background: #95a5a6; color: white; }
        .badge-estado.En_progreso { background: #f39c12; color: white; }
        .badge-estado.Completada { background: #27ae60; color: white; }
        .badge-estado.Cancelada { background: #e74c3c; color: white; }

        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);
            border-left: 5px solid var(--gris-oscuro);
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
        .input-group textarea { resize: vertical; min-height: 60px; }

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
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .meta-item .header { flex-direction: column; align-items: flex-start; }
            .modal-content { padding: 20px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .stats-grid { grid-template-columns: 1fr; }
            .meta-item .acciones { flex-direction: column; }
            .meta-item .acciones .btn-estado { text-align: center; }
            .btn-eliminar-meta { text-align: center; }
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
            <h1><i class='bx bxs-flag-checkered'></i> Mis Metas</h1>
            <p>Establece y da seguimiento a tus objetivos académicos</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button onclick="abrirModal()" class="btn-crear"><i class='bx bxs-plus-circle'></i> Nueva Meta</button>
            <a href="estudiante_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <?php if ($total > 0): ?>
        <div class="stats-grid">
            <div class="stat-item total">
                <div class="num"><?php echo $total; ?></div>
                <div class="label">📊 Total Metas</div>
            </div>
            <div class="stat-item completadas">
                <div class="num"><?php echo $completadas; ?></div>
                <div class="label">✅ Completadas</div>
            </div>
            <div class="stat-item progreso">
                <div class="num"><?php echo $en_progreso; ?></div>
                <div class="label">🔄 En Progreso</div>
            </div>
            <div class="stat-item pendientes">
                <div class="num"><?php echo $pendientes; ?></div>
                <div class="label">⏳ Pendientes</div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Lista de metas -->
    <?php if (count($metas) > 0): ?>
        <?php foreach ($metas as $m): ?>
            <div class="meta-item" style="border-left-color: <?php echo $colores_estado[$m['estado']]; ?>;">
                <div class="header">
                    <div>
                        <h3><?php echo htmlspecialchars($m['titulo']); ?></h3>
                        <div class="fechas">
                            <i class='bx bxs-calendar'></i> 
                            <?php echo date('d/m/Y', strtotime($m['fecha_inicio'])); ?> - 
                            <?php echo date('d/m/Y', strtotime($m['fecha_fin'])); ?>
                        </div>
                    </div>
                    <div>
                        <span class="badge-estado <?php echo str_replace(' ', '_', $m['estado']); ?>">
                            <?php echo $m['estado']; ?>
                        </span>
                    </div>
                </div>
                <?php if ($m['descripcion']): ?>
                    <div class="desc"><?php echo nl2br(htmlspecialchars($m['descripcion'])); ?></div>
                <?php endif; ?>
                <div class="acciones">
                    <?php foreach ($estados as $e): ?>
                        <?php if ($e != $m['estado']): ?>
                            <a href="estudiante_metas.php?estado=<?php echo urlencode($e); ?>&id=<?php echo $m['id']; ?>" 
                               class="btn-estado <?php echo strtolower(str_replace(' ', '', $e)); ?>">
                                <?php echo $e; ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <a href="estudiante_metas.php?eliminar=<?php echo $m['id']; ?>" 
                       class="btn-eliminar-meta" onclick="return confirm('¿Eliminar esta meta?')">
                        <i class='bx bxs-trash'></i> Eliminar
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-flag'></i>
            <h3>No tienes metas establecidas</h3>
            <p>¡Establece tus primeras metas académicas!</p>
            <button onclick="abrirModal()" class="btn-crear" style="margin-top: 15px; padding: 12px 30px; font-size: 16px;">
                <i class='bx bxs-plus-circle'></i> Crear mi primera meta
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para crear meta -->
<div class="modal" id="modalMeta">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class='bx bxs-flag'></i> Nueva Meta</h2>
            <span class="close" onclick="cerrarModal()">&times;</span>
        </div>
        <form method="POST">
            <div class="input-group">
                <label>Título de la Meta *</label>
                <input type="text" name="titulo" required placeholder="Ej: Terminar el semestre con promedio 9.0">
            </div>
            <div class="input-group">
                <label>Descripción</label>
                <textarea name="descripcion" placeholder="Describe tu meta en detalle..."></textarea>
            </div>
            <div class="input-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>Fecha Inicio *</label>
                    <input type="date" name="fecha_inicio" required>
                </div>
                <div>
                    <label>Fecha Fin *</label>
                    <input type="date" name="fecha_fin" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancelar" onclick="cerrarModal()">Cancelar</button>
                <button type="submit" name="crear_meta" class="btn-guardar"><i class='bx bxs-save'></i> Crear Meta</button>
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
        document.getElementById('modalMeta').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        document.getElementById('modalMeta').classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    document.getElementById('modalMeta').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') cerrarModal();
    });
</script>
</body>
</html>