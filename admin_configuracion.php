<?php
// admin_configuracion.php - Configuración del sistema (DISEÑO INSTITUCIONAL)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$mensaje = "";
$mensaje_tipo = "";

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// Obtener logo actual
$res = $conn->query("SELECT valor FROM configuracion WHERE clave = 'logo_universidad'");
$config = $res ? $res->fetch_assoc() : null;
$logo_actual = $config['valor'] ?? 'uploads/logo_universidad.png';

// ============================================================
// PROCESAR SUBIDA DE LOGO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['logo'])) {
    $directorio = "uploads/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $tipoArchivo = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
    
    if (in_array($tipoArchivo, ['jpg', 'jpeg', 'png'])) {
        $ruta_final = $directorio . "logo_universidad." . $tipoArchivo;
        
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $ruta_final)) {
            // Verificar si ya existe la configuración
            $check = $conn->query("SELECT COUNT(*) as total FROM configuracion WHERE clave = 'logo_universidad'");
            
            if ($check->fetch_assoc()['total'] > 0) {
                $stmt = $conn->prepare("UPDATE configuracion SET valor = ? WHERE clave = 'logo_universidad'");
            } else {
                $stmt = $conn->prepare("INSERT INTO configuracion (clave, valor) VALUES ('logo_universidad', ?)");
            }
            
            $stmt->bind_param("s", $ruta_final);
            
            if ($stmt->execute()) {
                $mensaje = "✅ Logo actualizado correctamente.";
                $mensaje_tipo = "success";
                $logo_actual = $ruta_final;
            } else {
                $mensaje = "❌ Error al guardar en la base de datos.";
                $mensaje_tipo = "error";
            }
            $stmt->close();
        } else {
            $mensaje = "❌ Error al subir el archivo.";
            $mensaje_tipo = "error";
        }
    } else {
        $mensaje = "⚠️ Solo se permiten formatos JPG, JPEG y PNG.";
        $mensaje_tipo = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración · ITSZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            --texto-oscuro: #333333;
            --verde-exito: #27ae60;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
        }

        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* ===== HEADER ===== */
        .card-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 24px 32px;
            border-bottom: 4px solid var(--dorado);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-header h1 {
            color: var(--blanco);
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-header h1 i {
            color: var(--dorado);
            font-size: 1.4rem;
        }

        .card-header p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin-top: 4px;
            width: 100%;
        }

        /* ===== BODY ===== */
        .card-body {
            padding: 32px;
        }

        /* ===== MENSAJES ===== */
        .mensaje {
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 5px solid transparent;
            font-size: 14px;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border-left-color: var(--verde-exito);
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: var(--rojo);
        }

        .mensaje i {
            font-size: 18px;
        }

        /* ===== TÍTULO DE SECCIÓN ===== */
        .section-title {
            color: var(--azul);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--dorado);
        }

        /* ===== PREVIEW DEL LOGO ===== */
        .preview-container {
            background: var(--gris);
            border: 2px dashed var(--gris-oscuro);
            border-radius: var(--radius-lg);
            padding: 24px;
            text-align: center;
            margin-bottom: 24px;
            transition: all 0.3s;
        }

        .preview-container:hover {
            border-color: var(--azul);
            background: #f8f9fa;
        }

        .preview-container .preview-label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 12px;
        }

        .preview-container img {
            max-width: 100%;
            max-height: 180px;
            display: block;
            margin: 0 auto;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            background: var(--blanco);
            padding: 8px;
        }

        /* ===== INPUT FILE PERSONALIZADO ===== */
        .file-input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 24px;
            background: #e8f0fe;
            border: 2px dashed var(--azul);
            border-radius: var(--radius);
            color: var(--azul);
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            cursor: pointer;
        }

        .file-input-label:hover {
            background: #d6e4f8;
            border-color: var(--guinda);
            color: var(--guinda);
        }

        .file-input-label i {
            font-size: 1.3rem;
        }

        .file-input-wrapper input[type="file"]:valid + .file-input-label {
            background: #d4edda;
            border-color: var(--verde-exito);
            color: #155724;
        }

        /* ===== INFO BOX ===== */
        .info-box {
            background: #fff8e1;
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--dorado);
            font-size: 13px;
            color: var(--texto-oscuro);
            line-height: 1.5;
        }

        .info-box i {
            color: var(--dorado);
            margin-right: 8px;
        }

        .info-box strong {
            color: var(--azul);
        }

        /* ===== BOTONES ===== */
        .btn {
            width: 100%;
            padding: 14px 24px;
            border: none;
            border-radius: var(--radius);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            box-shadow: 0 6px 20px rgba(27, 57, 106, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(27, 57, 106, 0.4);
        }

        .btn-secondary {
            background: var(--gris);
            color: var(--texto-oscuro);
            margin-top: 12px;
        }

        .btn-secondary:hover {
            background: var(--gris-oscuro);
            transform: translateY(-2px);
        }

        /* ===== FOOTER ===== */
        .footer-info {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--gris);
            font-size: 12px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }

        .footer-info i {
            color: var(--dorado);
            margin-right: 6px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            body { padding: 12px; }
            .card-header { padding: 18px 20px; }
            .card-header h1 { font-size: 18px; }
            .card-body { padding: 20px; }
            .preview-container { padding: 16px; }
            .preview-container img { max-height: 120px; }
            .file-input-label { padding: 14px 16px; font-size: 13px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <!-- ===== HEADER ===== -->
        <div class="card-header">
            <div style="display: flex; align-items: center; width: 100%; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                <div>
                    <h1><i class="fas fa-cog"></i> Configuración</h1>
                    <p>Personaliza el logo institucional del sistema</p>
                </div>
            </div>
        </div>

        <!-- ===== BODY ===== -->
        <div class="card-body">
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <i class="fas <?php echo $mensaje_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <!-- ===== VISTA PREVIA ===== -->
            <h3 class="section-title">
                <i class="fas fa-image"></i> Logo de la Universidad
            </h3>

            <div class="preview-container">
                <span class="preview-label">Vista Previa Actual</span>
                <img id="preview" 
                     src="<?php echo $logo_actual; ?>?t=<?php echo time(); ?>" 
                     alt="Logo Universidad"
                     onerror="this.src='uploads/logo_universidad.png'">
            </div>

            <!-- ===== FORMULARIO ===== -->
            <form method="post" enctype="multipart/form-data">
                
                <!-- Input File Personalizado -->
                <div class="file-input-wrapper">
                    <input type="file" 
                           name="logo" 
                           id="logoInput" 
                           accept="image/png, image/jpeg, image/jpg" 
                           required>
                    <label for="logoInput" class="file-input-label">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <span id="fileLabel">Seleccionar archivo</span>
                    </label>
                </div>

                <!-- Info Box -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <strong>Formatos permitidos:</strong> JPG, JPEG, PNG<br>
                    <strong>Tamaño recomendado:</strong> 500x500 píxeles<br>
                    <strong>Tamaño máximo:</strong> 2 MB
                </div>

                <!-- Botones -->
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>

                <a href="admin_panel.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Panel
                </a>
            </form>

            <!-- ===== FOOTER INFO ===== -->
            <div class="footer-info">
                <i class="fas fa-info-circle"></i>
                El logo se mostrará en el encabezado de todo el sistema
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPT PARA PREVISUALIZAR EL LOGO
     ============================================================ -->
<script>
    document.getElementById('logoInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const fileLabel = document.getElementById('fileLabel');
        const preview = document.getElementById('preview');

        if (file) {
            // Actualizar etiqueta
            fileLabel.textContent = file.name;

            // Previsualizar
            const reader = new FileReader();
            reader.onload = function(event) {
                preview.src = event.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            fileLabel.textContent = 'Seleccionar archivo';
        }
    });
</script>

</body>
</html>