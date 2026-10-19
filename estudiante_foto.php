<?php
// estudiante_foto.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

$usuario = $_SESSION['username'];
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$mensaje = "";
$mensaje_tipo = "";

// Obtener ID y foto actual
$sql_u = "SELECT Id, foto_perfil FROM usuarios WHERE Nombre = ?";
$stmt = $conn->prepare($sql_u);
$stmt->bind_param("s", $usuario);
$stmt->execute();
$datos_usuario = $stmt->get_result()->fetch_assoc();

$id_usuario = $datos_usuario['Id'];
$ruta_foto_db = $datos_usuario['foto_perfil'];

// Procesar subida
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['foto'])) {
    $directorio = "uploads/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $tipoArchivo = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
    if ($tipoArchivo == "jpg" || $tipoArchivo == "png" || $tipoArchivo == "jpeg") {
        $nombre_final = "foto_" . $id_usuario . "_" . time() . "." . $tipoArchivo;
        $ruta_final = $directorio . $nombre_final;
        
        if (move_uploaded_file($_FILES["foto"]["tmp_name"], $ruta_final)) {
            $sql = "UPDATE usuarios SET foto_perfil = ? WHERE Id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $ruta_final, $id_usuario);
            if ($stmt->execute()) {
                $mensaje = "✅ Foto actualizada correctamente.";
                $mensaje_tipo = "success";
                $ruta_foto_db = $ruta_final;
            } else {
                $mensaje = "❌ Error al guardar en base de datos.";
                $mensaje_tipo = "error";
            }
        } else {
            $mensaje = "❌ Error al mover el archivo.";
            $mensaje_tipo = "error";
        }
    } else {
        $mensaje = "⚠️ Solo formatos JPG o PNG.";
        $mensaje_tipo = "warning";
    }
}
$conn->close();

// Determinar imagen a mostrar
$imagen_a_mostrar = !empty($ruta_foto_db) && file_exists($ruta_foto_db) 
                   ? $ruta_foto_db . "?t=" . time() 
                   : "uploads/default_usuario.png";

$tiene_foto = !empty($ruta_foto_db) && file_exists($ruta_foto_db);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Foto de Perfil - ITSZ</title>
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

        .card {
            background: var(--blanco);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 480px;
            width: 100%;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .card .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .card .logo img {
            max-height: 70px;
            margin-bottom: 8px;
        }
        .card .logo h1 {
            color: var(--azul);
            font-size: 22px;
        }
        .card .logo p {
            color: var(--guinda);
            font-size: 13px;
            font-weight: 500;
        }

        .preview-container {
            margin: 20px 0;
            position: relative;
            display: inline-block;
        }
        .foto-marco {
            width: 150px;
            height: 180px;
            object-fit: cover;
            border: 5px solid var(--blanco);
            box-shadow: var(--shadow-hover);
            border-radius: 8px;
            background: var(--gris);
            transition: all 0.3s;
        }
        .foto-marco:hover {
            transform: scale(1.02);
        }
        .estado-texto {
            display: block;
            margin-top: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--gris-oscuro);
        }
        .estado-texto .sin-foto { color: var(--rojo); }
        .estado-texto .con-foto { color: #27ae60; }

        .mensaje {
            padding: 12px 15px;
            border-radius: var(--radius);
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 14px;
            display: <?php echo $mensaje ? 'block' : 'none'; ?>;
        }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .mensaje.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }

        .form-group {
            margin: 20px 0;
            text-align: left;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 5px;
            font-size: 14px;
        }
        .form-group .hint {
            font-size: 12px;
            color: var(--gris-oscuro);
            margin-bottom: 10px;
        }
        .form-group input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed var(--gris-oscuro);
            border-radius: var(--radius);
            background: var(--gris);
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
        }
        .form-group input[type="file"]:hover {
            border-color: var(--azul);
            background: var(--blanco);
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            width: 100%;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
        }

        .links {
            margin-top: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .links a {
            padding: 10px 20px;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .links a:hover { transform: translateY(-2px); }
        .links .btn-panel { background: var(--gris-oscuro); color: var(--blanco); }
        .links .btn-panel:hover { background: #b0b0b0; }
        .links .btn-carga { background: var(--dorado); color: var(--blanco); }
        .links .btn-carga:hover { background: #c78a2a; }

        @media (max-width: 480px) {
            .card { padding: 25px; margin: 10px; }
            .card .logo h1 { font-size: 18px; }
            .foto-marco { width: 120px; height: 145px; }
            .links { flex-direction: column; }
            .links a { justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Logo -->
        <div class="logo">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <h1>📸 Mi Foto de Perfil</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <!-- Mensaje -->
        <?php if ($mensaje): ?>
            <div class="mensaje <?php echo $mensaje_tipo; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Vista previa -->
        <div class="preview-container">
            <img src="<?php echo $imagen_a_mostrar; ?>" alt="Foto de perfil" class="foto-marco" id="preview">
            <span class="estado-texto">
                <?php if ($tiene_foto): ?>
                    <span class="con-foto">✅ Foto actual</span>
                <?php else: ?>
                    <span class="sin-foto">📷 Sin foto de perfil</span>
                <?php endif; ?>
            </span>
        </div>

        <!-- Formulario -->
        <form method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label><i class='bx bxs-cloud-upload'></i> Sube una nueva foto</label>
                <div class="hint">
                    <i class='bx bx-info-circle'></i> Recomendado: Fondo blanco, rostro despejado (JPG/PNG).
                </div>
                <input type="file" name="foto" id="fotoInput" accept="image/png, image/jpeg" required>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class='bx bxs-save'></i> Guardar Foto
            </button>
        </form>

        <!-- Enlaces -->
        <div class="links">
            <a href="estudiante_panel.php" class="btn-panel">
                <i class='bx bx-arrow-back'></i> Volver al Panel
            </a>
            <a href="carga.php" class="btn-carga">
                <i class='bx bxs-file-pdf'></i> Ver mi Carga
            </a>
        </div>
    </div>

    <script>
        // Vista previa de la imagen seleccionada
        document.getElementById('fotoInput').onchange = function (evt) {
            const [file] = this.files;
            if (file) {
                document.getElementById('preview').src = URL.createObjectURL(file);
                // Cambiar el estado a "Previsualizando"
                const estado = document.querySelector('.estado-texto');
                estado.innerHTML = '<span style="color: var(--dorado);">🔄 Previsualizando...</span>';
            }
        }
    </script>
</body>
</html>