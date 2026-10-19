<?php
// admin_ver_solicitudes_becas.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Seguridad: Solo Administradores
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$mensaje = "";
$mensaje_tipo = "";

// ✅ VERIFICAR QUE LA TABLA EXISTA
$tabla_existe = $conn->query("SHOW TABLES LIKE 'solicitudes_becas'");
if ($tabla_existe->num_rows === 0) {
    $mensaje = "⚠️ La tabla 'solicitudes_becas' no existe. Contacta al administrador.";
    $mensaje_tipo = "error";
}

// Procesar cambio de estado (Aprobar / Rechazar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_estado']) && $tabla_existe->num_rows > 0) {
    $id_solicitud = intval($_POST['id_solicitud']);
    $nuevo_estado = $_POST['estado'];
    $comentario_admin = trim($_POST['comentario_admin']);

    $stmt = $conn->prepare("UPDATE solicitudes_becas SET estado = ?, comentario_admin = ? WHERE id = ?");
    $stmt->bind_param("ssi", $nuevo_estado, $comentario_admin, $id_solicitud);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Estado actualizado correctamente.";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al actualizar: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

// Obtener solicitudes
$solicitudes = [];
if ($tabla_existe->num_rows > 0) {
    // ✅ CORREGIDO: Usar 'id' en lugar de 'id' (la columna se llama 'id' en la tabla usuarios)
    $sql = "SELECT s.*, u.Nombre as nombre_alumno 
            FROM solicitudes_becas s 
            JOIN usuarios u ON s.id_estudiante = u.Id 
            ORDER BY s.fecha_solicitud DESC";
    $solicitudes = $conn->query($sql);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Solicitudes de Becas - ITSZ</title>
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

        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .alert.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .card { background: #fff; padding: 20px; border-radius: var(--radius); box-shadow: var(--shadow); margin-bottom: 20px; border-left: 5px solid var(--azul); }
        .card-pendiente { border-left-color: #f39c12; }
        .card-aprobada { border-left-color: #27ae60; }
        .card-rechazada { border-left-color: var(--rojo); }

        .solicitud-header { display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; }
        .solicitud-header h3 { margin: 0 0 5px 0; color: var(--azul); }
        .solicitud-meta { font-size: 0.9rem; color: var(--gris-oscuro); }

        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-pendiente { background: #ffeeba; color: #856404; }
        .badge-aprobada { background: #c3e6cb; color: #155724; }
        .badge-rechazada { background: #f8d7da; color: #721c24; }

        .form-actualizar { margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--gris); display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-size: 0.8rem; font-weight: 600; color: #555; margin-bottom: 4px; }
        .form-group select, .form-group textarea { padding: 8px 12px; border: 2px solid var(--gris-oscuro); border-radius: var(--radius); font-size: 14px; background: white; }
        .form-group select:focus, .form-group textarea:focus { border-color: var(--azul); outline: none; }
        .form-group textarea { width: 300px; height: 40px; resize: vertical; }
        .btn-update { background: var(--azul); color: white; border: none; padding: 8px 20px; border-radius: var(--radius); cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-update:hover { background: var(--guinda); transform: translateY(-2px); }

        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius); box-shadow: var(--shadow); }
        .empty-state i { font-size: 50px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
        .empty-state p { color: var(--gris-oscuro); font-size: 16px; }

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
            .form-actualizar { flex-direction: column; align-items: stretch; }
            .form-group textarea { width: 100%; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .solicitud-header { flex-direction: column; }
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
        <div><h1><i class='bx bxs-award'></i> Solicitudes de Becas</h1><p>Administra y responde las solicitudes de los estudiantes.</p></div>
        <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <?php if ($tabla_existe && $tabla_existe->num_rows > 0 && $solicitudes && $solicitudes->num_rows > 0): ?>
        <?php while($row = $solicitudes->fetch_assoc()): 
            $estado_class = strtolower($row['estado']);
        ?>
            <div class="card card-<?php echo $estado_class; ?>">
                <div class="solicitud-header">
                    <div>
                        <h3><?php echo htmlspecialchars($row['nombre_alumno']); ?></h3>
                        <div class="solicitud-meta">
                            <strong>Beca ID:</strong> <?php echo htmlspecialchars($row['id_beca']); ?><br>
                            <strong>Matrícula:</strong> <?php echo htmlspecialchars($row['matricula']); ?> | 
                            <strong>Promedio:</strong> <?php echo $row['promedio']; ?><br>
                            <strong>Fecha:</strong> <?php echo $row['fecha_solicitud']; ?>
                        </div>
                    </div>
                    <div>
                        <span class="badge badge-<?php echo $estado_class; ?>"><?php echo $row['estado']; ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 10px; background: var(--gris); padding: 10px; border-radius: var(--radius);">
                    <strong>Motivo:</strong><br>
                    <?php echo nl2br(htmlspecialchars($row['motivo'])); ?>
                </div>

                <?php if (!empty($row['archivo'])): ?>
                    <div style="margin-top: 10px;">
                        <a href="<?php echo $row['archivo']; ?>" target="_blank" style="color: var(--azul); font-weight: 600;">
                            <i class='bx bxs-file-pdf'></i> Ver documento adjunto
                        </a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($row['comentario_admin'])): ?>
                    <div style="margin-top: 10px; background: #e8f4f8; padding: 10px; border-radius: var(--radius); border-left: 4px solid var(--azul);">
                        <strong>💬 Comentario del Admin:</strong><br>
                        <?php echo nl2br(htmlspecialchars($row['comentario_admin'])); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-actualizar">
                    <input type="hidden" name="id_solicitud" value="<?php echo $row['id']; ?>">
                    <div class="form-group">
                        <label>Nuevo Estado</label>
                        <select name="estado">
                            <option value="Pendiente" <?php echo ($row['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                            <option value="Aprobada" <?php echo ($row['estado'] == 'Aprobada') ? 'selected' : ''; ?>>Aprobar</option>
                            <option value="Rechazada" <?php echo ($row['estado'] == 'Rechazada') ? 'selected' : ''; ?>>Rechazar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Comentario (opcional)</label>
                        <textarea name="comentario_admin" placeholder="Ej: Falta documentación, beca otorgada..."><?php echo htmlspecialchars($row['comentario_admin'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" name="actualizar_estado" class="btn-update"><i class='bx bxs-save'></i> Guardar</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php elseif ($tabla_existe && $tabla_existe->num_rows > 0): ?>
        <div class="empty-state">
            <i class='bx bxs-inbox'></i>
            <p>No hay solicitudes de becas registradas aún.</p>
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