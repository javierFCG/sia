<?php
// subir_tarea.php - Subir tarea para estudiantes
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Estudiante') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id_tarea']) || !is_numeric($_GET['id_tarea'])) {
    die("Error: No se especificó la tarea.");
}

$id_tarea = (int)$_GET['id_tarea'];
$id_estudiante = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// Obtener información de la tarea
$stmt = $conn->prepare("SELECT t.*, m.nombre as materia_nombre 
                         FROM tareas t 
                         JOIN materias m ON t.materia_id = m.id 
                         WHERE t.id = ?");
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$tarea = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tarea) {
    die("Error: Tarea no encontrada.");
}

// Verificar si ya entregó
$check = $conn->prepare("SELECT id FROM entregas_tareas WHERE id_tarea = ? AND id_estudiante = ?");
$check->bind_param("ii", $id_tarea, $id_estudiante);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    die("⚠️ Ya has entregado esta tarea. No puedes subir otro archivo.");
}
$check->close();

$mensaje = "";
$mensaje_tipo = "";

// Procesar subida de archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo'])) {
    $target_dir = "uploads/";
    
    // Crear la carpeta 'uploads' si no existe
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $nombre_original = basename($_FILES["archivo"]["name"]);
    $extension = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
    $tipos_permitidos = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt', 'zip', 'rar'];
    
    if (!in_array($extension, $tipos_permitidos)) {
        $mensaje = "❌ Tipo de archivo no permitido. Solo: " . implode(', ', $tipos_permitidos);
        $mensaje_tipo = "error";
    } elseif ($_FILES["archivo"]["size"] > 20 * 1024 * 1024) {
        $mensaje = "❌ El archivo es demasiado grande. Máximo 20MB.";
        $mensaje_tipo = "error";
    } else {
        // Generar nombre único
        $nuevo_nombre = "tarea_" . $id_tarea . "_estudiante_" . $id_estudiante . "." . $extension;
        $target_file = $target_dir . $nuevo_nombre;

        if (move_uploaded_file($_FILES["archivo"]["tmp_name"], $target_file)) {
            // Guardar en la base de datos
            $stmt = $conn->prepare("INSERT INTO entregas_tareas (id_tarea, id_estudiante, nombre_archivo, ruta_archivo) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $id_tarea, $id_estudiante, $nombre_original, $target_file);
            
            if ($stmt->execute()) {
                $mensaje = "✅ ¡Tarea subida exitosamente!";
                $mensaje_tipo = "success";
            } else {
                $mensaje = "❌ Error al registrar en la BD: " . $stmt->error;
                $mensaje_tipo = "error";
            }
            $stmt->close();
        } else {
            $mensaje = "❌ Error al subir el archivo. Verifica los permisos de la carpeta.";
            $mensaje_tipo = "error";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Tarea - ITSZ</title>
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
        body {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .upload-container {
            background: var(--blanco);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 550px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .upload-container .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .upload-container .logo img {
            max-height: 70px;
            margin-bottom: 8px;
        }
        .upload-container .logo h1 {
            color: var(--azul);
            font-size: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .upload-container .logo h1 i { color: var(--guinda); }
        .upload-container .logo p {
            color: var(--guinda);
            font-size: 13px;
            font-weight: 500;
        }

        .tarea-info {
            background: #f8f5f0;
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--azul);
        }
        .tarea-info .label {
            font-size: 12px;
            color: var(--gris-oscuro);
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .tarea-info .value {
            font-size: 16px;
            font-weight: 700;
            color: var(--azul);
        }
        .tarea-info .value i { color: var(--guinda); margin-right: 5px; }

        .mensaje {
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-weight: 600;
            display: <?php echo $mensaje ? 'block' : 'none'; ?>;
        }
        .mensaje.success {
            background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 8px;
            font-size: 14px;
        }
        .form-group label i {
            color: var(--guinda);
            margin-right: 5px;
        }
        .form-group .hint {
            font-size: 12px;
            color: var(--gris-oscuro);
            margin-top: 5px;
        }
        .form-group input[type="file"] {
            width: 100%;
            padding: 15px;
            border: 2px dashed var(--gris-oscuro);
            border-radius: var(--radius);
            background: var(--gris);
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }
        .form-group input[type="file"]:hover {
            border-color: var(--azul);
            background: var(--blanco);
        }

        .btn-subir {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-subir:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: var(--gris-oscuro);
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s;
            font-size: 14px;
        }
        .back-link:hover {
            color: var(--azul);
        }

        .footer-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            padding: 20px;
            text-align: center;
            margin-top: 40px;
        }
        .footer-itsz .container { max-width: 1200px; margin: 0 auto; }
        .footer-itsz .social-links { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin: 10px 0; }
        .footer-itsz .social-links a { color: var(--blanco); font-size: 18px; transition: all 0.3s; }
        .footer-itsz .social-links a:hover { color: var(--dorado); transform: translateY(-3px); }
        .footer-itsz hr { border-color: rgba(255,255,255,0.15); margin: 10px 0; }
        .footer-itsz .copy { font-size: 11px; opacity: 0.8; }

        @media (max-width: 480px) {
            .upload-container { padding: 25px; margin: 10px; }
            .upload-container .logo h1 { font-size: 20px; }
            .upload-container .logo img { max-height: 50px; }
            .form-group input[type="file"] { padding: 12px; font-size: 13px; }
            .tarea-info .value { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <!-- Logo -->
        <div class="logo">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <h1><i class='bx bxs-upload'></i> Subir Tarea</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <!-- Información de la tarea -->
        <div class="tarea-info">
            <div class="label"><i class='bx bxs-user'></i> Estudiante</div>
            <div class="value"><?php echo htmlspecialchars($nombre_usuario); ?></div>
            <div style="margin-top: 8px;">
                <span class="label"><i class='bx bxs-book'></i> Materia</span>
                <div class="value" style="font-size: 14px;"><?php echo htmlspecialchars($tarea['materia_nombre']); ?></div>
            </div>
            <div style="margin-top: 8px;">
                <span class="label"><i class='bx bxs-task'></i> Tarea</span>
                <div class="value" style="font-size: 14px;"><?php echo htmlspecialchars($tarea['titulo']); ?></div>
            </div>
            <div style="margin-top: 8px;">
                <span class="label"><i class='bx bxs-time'></i> Fecha Límite</span>
                <div class="value" style="font-size: 14px; color: var(--rojo);">
                    <?php echo date('d/m/Y H:i', strtotime($tarea['fecha_limite'])); ?>
                </div>
            </div>
        </div>

        <!-- Mensaje -->
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $mensaje_tipo; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form action="subir_tarea.php?id_tarea=<?php echo $id_tarea; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="archivo"><i class='bx bxs-file'></i> Selecciona tu archivo</label>
                <input type="file" name="archivo" id="archivo" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.txt,.zip,.rar">
                <div class="hint">
                    <i class='bx bx-info-circle'></i> Formatos permitidos: PDF, Word, Imagen, TXT, ZIP, RAR (Máx. 20MB)
                </div>
            </div>

            <button type="submit" class="btn-subir">
                <i class='bx bxs-cloud-upload'></i> Subir Archivo
            </button>
        </form>

        <a href="estudiante_tareas.php" class="back-link">
            <i class='bx bx-arrow-back'></i> Volver a mis tareas
        </a>
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