<?php
// registro.php - SOLO PARA NUEVO INGRESO (SIN CARRERA, SEMESTRE Y GRUPO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Redirigir si ya está logueado
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;

    // Validaciones
    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "⚠️ Todos los campos obligatorios deben estar llenos.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "⚠️ El correo electrónico no es válido.";
    } elseif (strlen($password) < 6) {
        $error = "⚠️ La contraseña debe tener al menos 6 caracteres.";
    } else {
        // Verificar si el correo ya existe
        $stmt = $conn->prepare("SELECT Id FROM usuarios WHERE Correo = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = "❌ El correo electrónico ya está registrado. <a href='Login.html' style='color:#1B396A; font-weight:700;'>Inicia sesión aquí</a>";
        } else {
            $stmt->close();
            
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $rol = 'NuevoIngreso'; // Siempre Nuevo Ingreso
            
            // ✅ INSERT SIN carrera_id, semestre y grupo (los asigna el admin)
            $sql = "INSERT INTO usuarios (Nombre, Correo, `password`, Rol, telefono, direccion, fecha_nacimiento) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $nombre, $email, $hashed_pass, $rol, $telefono, $direccion, $fecha_nacimiento);

            if ($stmt->execute()) {
                $nuevo_id = $conn->insert_id;
                $_SESSION['user_id'] = $nuevo_id;
                $_SESSION['username'] = $nombre;
                $_SESSION['role'] = $rol;
                $_SESSION['nombre'] = $nombre;
                
                header("Location: nuevo_ingreso_documentos.php");
                exit();
            } else {
                $error = "❌ Error al registrar: " . $stmt->error;
            }
            $stmt->close();
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
    <title>Registro Nuevo Ingreso · ITSZ</title>
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
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
            --verde-exito: #27ae60;
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

        .register-container {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 520px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 24px 30px;
            border-bottom: 4px solid var(--dorado);
            text-align: center;
        }

        .header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 6px;
        }

        .header .logo i {
            color: var(--dorado);
            font-size: 2rem;
        }

        .header h1 {
            color: var(--blanco);
            font-weight: 800;
            font-size: 1.2rem;
        }

        .header h1 small {
            display: block;
            font-weight: 400;
            font-size: 0.7rem;
            color: rgba(255,255,255,0.7);
            letter-spacing: 0.3px;
        }

        .header .badge {
            display: inline-block;
            background: var(--dorado);
            color: var(--azul);
            padding: 4px 16px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .contenido {
            padding: 30px 32px 32px;
        }

        .titulo {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--azul);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .titulo i {
            color: var(--dorado);
        }

        .badge-nuevo {
            display: inline-block;
            background: var(--dorado);
            color: var(--azul);
            padding: 2px 12px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            margin-left: 6px;
            vertical-align: middle;
        }

        .subtitulo {
            color: var(--texto-oscuro);
            font-size: 0.85rem;
            opacity: 0.7;
            margin-bottom: 20px;
        }

        .subtitulo strong {
            color: var(--guinda);
            opacity: 1;
        }

        .separador {
            height: 1px;
            background: var(--gris-oscuro);
            margin: 16px 0 20px;
        }

        .input-group {
            margin-bottom: 16px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 4px;
            font-size: 0.8rem;
        }

        .input-group label i {
            color: var(--dorado);
            width: 20px;
        }

        .input-group .required {
            color: var(--rojo);
            font-weight: 700;
        }

        .input-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 0.95rem;
            transition: all 0.3s;
            box-sizing: border-box;
            background: var(--gris);
            font-family: 'Inter', sans-serif;
        }

        .input-group input:focus {
            border-color: var(--azul);
            outline: none;
            background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27, 57, 106, 0.08);
        }

        .input-group .help-text {
            font-size: 0.7rem;
            color: var(--texto-oscuro);
            opacity: 0.6;
            margin-top: 4px;
        }

        .input-group .help-text i {
            color: var(--dorado);
            margin-right: 4px;
        }

        .input-group .info-box {
            background: #fff8e1;
            padding: 12px 16px;
            border-radius: var(--radius);
            border-left: 4px solid var(--dorado);
            font-size: 0.8rem;
            color: var(--texto-oscuro);
        }

        .input-group .info-box i {
            color: var(--dorado);
            margin-right: 8px;
        }

        .row-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 8px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .error-msg {
            background: #F8D7DA;
            color: #721C24;
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            border: 1px solid #F5C6CB;
            font-size: 0.9rem;
            display: <?php echo $error ? 'block' : 'none'; ?>;
        }

        .error-msg a {
            color: var(--azul);
            font-weight: 700;
        }

        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 0.85rem;
            color: var(--gris-oscuro);
        }

        .login-link a {
            color: var(--azul);
            font-weight: 600;
            transition: color 0.3s;
            text-decoration: none;
        }

        .login-link a:hover {
            color: var(--guinda);
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            .register-container { margin: 10px; }
            .contenido { padding: 20px; }
            .row-2 { grid-template-columns: 1fr; gap: 0; }
            .header { padding: 18px 20px; }
            .header h1 { font-size: 1rem; }
            .titulo { font-size: 1.1rem; }
            .input-group input { padding: 10px 14px; font-size: 0.9rem; }
        }
    </style>
</head>
<body>

<div class="register-container">
    <!-- Header -->
    <div class="header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h1>
                Tecnológico Superior de Zongolica
                <small>Sistema Integral Académico</small>
            </h1>
        </div>
        <span class="badge"><i class="fas fa-file-upload"></i> Nuevo Ingreso</span>
    </div>

    <!-- Contenido -->
    <div class="contenido">
        <div class="titulo">
            <i class="fas fa-user-plus"></i> Registro de Nuevo Ingreso
            <span class="badge-nuevo">Nuevo</span>
        </div>
        <p class="subtitulo">
            Completa tus datos personales para iniciar tu proceso de inscripción en el <strong>Tecnológico Superior de Zongolica</strong>
        </p>

        <div class="separador"></div>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- Nombre Completo -->
            <div class="input-group">
                <label><i class="fas fa-user"></i> Nombre Completo <span class="required">*</span></label>
                <input type="text" name="username" placeholder="Ej: Juan Pérez Martínez" required>
            </div>

            <!-- Correo Electrónico -->
            <div class="input-group">
                <label><i class="fas fa-envelope"></i> Correo Electrónico <span class="required">*</span></label>
                <input type="email" name="email" placeholder="tu@correo.com" required>
                <div class="help-text">
                    <i class="fas fa-info-circle"></i> Usaremos este correo para enviarte tus credenciales institucionales.
                </div>
            </div>

            <!-- Contraseña -->
            <div class="input-group">
                <label><i class="fas fa-key"></i> Contraseña <span class="required">*</span></label>
                <input type="password" name="password" placeholder="Mínimo 6 caracteres" required>
                <div class="help-text">
                    <i class="fas fa-info-circle"></i> Crea una contraseña segura. Luego podrás cambiarla.
                </div>
            </div>

            <div class="row-2">
                <!-- Teléfono -->
                <div class="input-group">
                    <label><i class="fas fa-phone"></i> Teléfono</label>
                    <input type="text" name="telefono" placeholder="Ej: 2721234567">
                </div>

                <!-- Fecha de Nacimiento -->
                <div class="input-group">
                    <label><i class="fas fa-calendar"></i> Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento">
                </div>
            </div>

            <!-- Dirección -->
            <div class="input-group">
                <label><i class="fas fa-home"></i> Dirección</label>
                <input type="text" name="direccion" placeholder="Calle, número, colonia, ciudad">
            </div>

            <!-- Documentos Requeridos - Info -->
            <div class="input-group">
                <div class="info-box">
                    <i class="fas fa-file-alt"></i>
                    <strong>Documentos requeridos:</strong> Acta de Nacimiento, Certificado de Bachillerato, CURP, 
                    Comprobante de Domicilio, Fotografía Infantil, Ficha de Preinscripción TecNM
                </div>
            </div>

            <!-- Información sobre asignación de carrera -->
            <div class="input-group">
                <div class="info-box" style="background: #e8f0fe; border-left-color: var(--azul);">
                    <i class="fas fa-info-circle" style="color: var(--azul);"></i>
                    <strong>Nota:</strong> La carrera, semestre y grupo serán asignados por el administrador después de validar tus documentos.
                </div>
            </div>

            <button type="submit" class="btn-register">
                <i class="fas fa-arrow-right"></i> Registrarse
            </button>

            <div class="login-link">
                ¿Ya tienes cuenta? <a href="Login.html">Inicia sesión aquí</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>