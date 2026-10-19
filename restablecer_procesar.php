<?php
// restablecer_procesar.php - CORREGIDO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

// Verificar que los datos lleguen
$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (empty($token) || empty($password) || empty($confirm_password)) {
    header("Location: restablecer.php?token=" . $token . "&error=Todos los campos son requeridos");
    exit();
}

if ($password !== $confirm_password) {
    header("Location: restablecer.php?token=" . $token . "&error=Las contraseñas no coinciden");
    exit();
}

if (strlen($password) < 6) {
    header("Location: restablecer.php?token=" . $token . "&error=La contraseña debe tener al menos 6 caracteres");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Verificar token
$stmt = $conn->prepare("SELECT Id, token_expiracion FROM usuarios WHERE token_recuperacion = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header("Location: recuperar.php?error=Token inválido");
    exit();
}

$usuario = $result->fetch_assoc();
$expiracion = strtotime($usuario['token_expiracion']);

if (time() > $expiracion) {
    $conn->close();
    header("Location: recuperar.php?error=El enlace ha expirado");
    exit();
}

$hashed_pass = password_hash($password, PASSWORD_DEFAULT);

// ✅ CORREGIDO: Usar el nombre correcto de la columna "password"
$stmt = $conn->prepare("UPDATE usuarios SET password = ?, token_recuperacion = NULL, token_expiracion = NULL WHERE Id = ?");
$stmt->bind_param("si", $hashed_pass, $usuario['Id']);

if ($stmt->execute()) {
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Contraseña Actualizada</title>
        <style>
            body {
                font-family: 'Segoe UI', sans-serif;
                background: linear-gradient(135deg, #1B396A 0%, #620F31 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            .container {
                background: white;
                padding: 40px;
                border-radius: 20px;
                max-width: 500px;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            .icon {
                font-size: 60px;
                color: #27ae60;
                margin-bottom: 20px;
            }
            h1 {
                color: #1B396A;
                font-size: 28px;
                margin-bottom: 10px;
            }
            p {
                color: #666;
                font-size: 16px;
                margin-bottom: 20px;
            }
            .btn {
                display: inline-block;
                padding: 12px 35px;
                background: linear-gradient(135deg, #1B396A 0%, #620F31 100%);
                color: white;
                text-decoration: none;
                border-radius: 30px;
                font-weight: 700;
                transition: 0.3s;
            }
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(27, 57, 106, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">✅</div>
            <h1>¡Contraseña Actualizada!</h1>
            <p>Tu contraseña ha sido restablecida exitosamente.</p>
            <a href="Login.html" class="btn">Iniciar Sesión</a>
        </div>
    </body>
    </html>
    <?php
} else {
    $conn->close();
    header("Location: restablecer.php?token=" . $token . "&error=Error al actualizar la contraseña");
    exit();
}
?>