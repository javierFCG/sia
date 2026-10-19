<?php
// restablecer.php - CON DISEÑO ITSZ
session_start();
require_once 'config.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    header("Location: recuperar.php?error=Token inválido");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$stmt = $conn->prepare("SELECT Id, Nombre, token_expiracion FROM usuarios WHERE token_recuperacion = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $conn->close();
    header("Location: recuperar.php?error=Token inválido o expirado");
    exit();
}

$usuario = $result->fetch_assoc();

$expiracion = strtotime($usuario['token_expiracion']);
if (time() > $expiracion) {
    $conn->close();
    header("Location: recuperar.php?error=El enlace ha expirado. Solicita uno nuevo.");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', 'Poppins', sans-serif; }
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 20px;
        }
        .container {
            background: var(--blanco);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 450px;
            width: 100%;
        }
        .container .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .container .logo img {
            max-height: 70px;
            margin-bottom: 8px;
        }
        .container .logo h1 {
            color: var(--azul);
            font-size: 22px;
        }
        .container .logo p {
            color: var(--guinda);
            font-size: 13px;
            font-weight: 500;
        }
        .user-info {
            background: #f8f5f0;
            padding: 12px 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--azul);
            text-align: center;
        }
        .user-info strong {
            color: var(--azul);
        }
        .input-box {
            position: relative;
            margin: 20px 0;
        }
        .input-box input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            background: var(--gris);
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            outline: none;
            font-size: 15px;
            color: #333;
            box-sizing: border-box;
            transition: all 0.3s;
        }
        .input-box input:focus {
            border-color: var(--azul);
            background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27, 57, 106, 0.1);
        }
        .input-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: var(--azul);
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            font-size: 16px;
            color: var(--blanco);
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: var(--azul);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .back-link:hover {
            color: var(--guinda);
            text-decoration: underline;
        }
        .mensaje {
            padding: 15px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-weight: 500;
            display: none;
            font-size: 14px;
        }
        .mensaje.error {
            display: block;
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
        }
        .password-requirements {
            text-align: left;
            font-size: 12px;
            color: var(--gris-oscuro);
            margin-top: -10px;
            margin-bottom: 15px;
        }
        @media (max-width: 480px) {
            .container { padding: 25px; margin: 10px; }
            .container .logo h1 { font-size: 18px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <h1>Restablecer Contraseña</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <div class="user-info">
            👤 Usuario: <strong><?php echo htmlspecialchars($usuario['Nombre']); ?></strong>
        </div>

        <div id="mensaje" class="mensaje"></div>

        <form id="formRestablecer" action="restablecer_procesar.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="input-box">
                <input type="password" id="password" name="password" placeholder="Nueva Contraseña" required>
                <i class="bx bxs-lock-alt"></i>
            </div>

            <div class="input-box">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirmar Contraseña" required>
                <i class="bx bxs-check-circle"></i>
            </div>

            <div class="password-requirements">
                🔒 La contraseña debe tener al menos 6 caracteres.
            </div>

            <button type="submit" class="btn">Actualizar Contraseña</button>
        </form>

        <a href="Login.html" class="back-link">⬅️ Volver al Login</a>
    </div>

    <script>
        document.getElementById('formRestablecer').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirm = document.getElementById('confirm_password').value;
            const mensaje = document.getElementById('mensaje');

            if (password.length < 6) {
                e.preventDefault();
                mensaje.className = 'mensaje error';
                mensaje.textContent = '❌ La contraseña debe tener al menos 6 caracteres.';
                return;
            }

            if (password !== confirm) {
                e.preventDefault();
                mensaje.className = 'mensaje error';
                mensaje.textContent = '❌ Las contraseñas no coinciden.';
                return;
            }
        });

        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('error')) {
            const mensaje = document.getElementById('mensaje');
            mensaje.className = 'mensaje error';
            mensaje.textContent = '❌ ' + urlParams.get('error');
        }
    </script>
</body>
</html>