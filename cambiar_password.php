<?php
// cambiar_password.php - Cambiar contraseña desde el panel
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$mensaje = "";
$error = "";

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'];
    $password_nueva = $_POST['password_nueva'];
    $password_confirmar = $_POST['password_confirmar'];
    
    // Verificar contraseña actual
    $stmt = $conn->prepare("SELECT `Contraseña` FROM usuarios WHERE Id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    if (!password_verify($password_actual, $usuario['Contraseña'])) {
        $error = "❌ La contraseña actual es incorrecta.";
    } elseif (strlen($password_nueva) < 6) {
        $error = "⚠️ La nueva contraseña debe tener al menos 6 caracteres.";
    } elseif ($password_nueva !== $password_confirmar) {
        $error = "⚠️ Las contraseñas no coinciden.";
    } else {
        $hashed = password_hash($password_nueva, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET `Contraseña` = ? WHERE Id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        if ($stmt->execute()) {
            $mensaje = "✅ Contraseña actualizada correctamente.";
        } else {
            $error = "❌ Error al actualizar la contraseña.";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña · ITSZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --texto-oscuro: #333333;
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
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 450px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 20px 30px;
            border-bottom: 4px solid var(--dorado);
            text-align: center;
        }

        .header h1 {
            color: var(--blanco);
            font-weight: 800;
            font-size: 1.2rem;
        }

        .header h1 i {
            color: var(--dorado);
            margin-right: 10px;
        }

        .header p {
            color: rgba(255,255,255,0.8);
            font-size: 0.8rem;
            margin-top: 2px;
        }

        .contenido {
            padding: 30px 32px 32px;
        }

        .info-correo {
            background: var(--gris);
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--dorado);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-correo i {
            color: var(--dorado);
            font-size: 1.2rem;
        }

        .info-correo .correo {
            font-weight: 700;
            color: var(--azul);
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

        .mensaje {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid transparent;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border-left-color: #27ae60;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #e74c3c;
        }

        .btn-cambiar {
            width: 100%;
            padding: 14px;
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
            margin-top: 4px;
        }

        .btn-cambiar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-volver {
            display: block;
            text-align: center;
            margin-top: 16px;
            color: var(--azul);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .btn-volver:hover {
            color: var(--guinda);
            text-decoration: underline;
        }

        .requirements {
            font-size: 0.75rem;
            color: var(--texto-oscuro);
            opacity: 0.6;
            margin-top: -8px;
            margin-bottom: 12px;
        }

        .requirements i {
            color: var(--dorado);
            margin-right: 4px;
        }

        @media (max-width: 480px) {
            .contenido { padding: 20px; }
            .header { padding: 16px 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1><i class="fas fa-key"></i> Cambiar Contraseña</h1>
        <p>Actualiza tu contraseña de acceso al sistema</p>
    </div>

    <div class="contenido">
        <?php if ($mensaje): ?>
            <div class="mensaje success"><i class="fas fa-check-circle"></i> <?php echo $mensaje; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mensaje error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="info-correo">
            <i class="fas fa-envelope"></i>
            <div>
                <span style="font-size: 0.75rem; opacity: 0.6;">Correo institucional:</span><br>
                <span class="correo"><?php echo htmlspecialchars($_SESSION['correo_institucional'] ?? $_SESSION['username']); ?></span>
            </div>
        </div>

        <form method="POST">
            <div class="input-group">
                <label><i class="fas fa-lock"></i> Contraseña Actual</label>
                <input type="password" name="password_actual" placeholder="Ingresa tu contraseña actual" required>
            </div>

            <div class="input-group">
                <label><i class="fas fa-key"></i> Nueva Contraseña</label>
                <input type="password" name="password_nueva" placeholder="Mínimo 6 caracteres" required>
            </div>

            <div class="requirements">
                <i class="fas fa-info-circle"></i> La contraseña debe tener al menos 6 caracteres
            </div>

            <div class="input-group">
                <label><i class="fas fa-check-circle"></i> Confirmar Nueva Contraseña</label>
                <input type="password" name="password_confirmar" placeholder="Repite tu nueva contraseña" required>
            </div>

            <button type="submit" class="btn-cambiar">
                <i class="fas fa-save"></i> Actualizar Contraseña
            </button>
        </form>

        <a href="index.php" class="btn-volver"><i class="fas fa-arrow-left"></i> Volver al inicio</a>
    </div>
</div>

</body>
</html>