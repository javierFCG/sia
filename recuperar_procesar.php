<?php
// recuperar_procesar.php - CON DISEÑO ITSZ
session_start();
require_once 'config.php';

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    header("Location: recuperar.php?error=Correo electrónico requerido");
    exit();
}

$stmt = $conn->prepare("SELECT Id, Nombre FROM usuarios WHERE Correo = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: recuperar.php?error=No existe una cuenta con este correo");
    exit();
}

$usuario = $result->fetch_assoc();

$token = bin2hex(random_bytes(32));
$expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

$stmt = $conn->prepare("UPDATE usuarios SET token_recuperacion = ?, token_expiracion = ? WHERE Id = ?");
$stmt->bind_param("ssi", $token, $expiracion, $usuario['Id']);
$stmt->execute();

$enlace = SITE_URL . "restablecer.php?token=" . $token;
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enlace de Recuperación - ITSZ</title>
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
            max-width: 520px;
            width: 100%;
        }
        .container .logo {
            text-align: center;
            margin-bottom: 20px;
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
            font-size: 15px;
        }
        .user-info strong {
            color: var(--azul);
        }
        .enlace-box {
            background: var(--gris);
            padding: 20px;
            border-radius: var(--radius);
            margin: 20px 0;
            word-break: break-all;
            border: 2px dashed var(--gris-oscuro);
        }
        .enlace-box a {
            color: var(--azul);
            font-weight: 600;
            text-decoration: underline;
            font-size: 14px;
        }
        .enlace-box a:hover {
            color: var(--guinda);
        }
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            border-radius: var(--radius);
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            min-width: 120px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
        }
        .btn-secondary {
            background: var(--gris-oscuro);
            color: #333;
        }
        .btn-secondary:hover {
            background: #bdbdbd;
        }
        .btn-success {
            background: var(--dorado);
            color: var(--blanco);
        }
        .btn-success:hover {
            background: var(--dorado-intenso);
        }
        .info-text {
            text-align: center;
            font-size: 13px;
            color: var(--gris-oscuro);
            margin-top: 15px;
        }
        @media (max-width: 480px) {
            .container { padding: 25px; margin: 10px; }
            .container .logo h1 { font-size: 18px; }
            .btn-group { flex-direction: column; }
            .btn { min-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <h1>🔗 Enlace de Recuperación</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <div class="user-info">
            👤 Usuario: <strong><?php echo htmlspecialchars($usuario['Nombre']); ?></strong>
        </div>

        <p class="info-text" style="margin-bottom:15px;">Este es tu enlace para restablecer la contraseña <strong>(válido por 1 hora)</strong></p>

        <div class="enlace-box">
            <a href="<?php echo $enlace; ?>" target="_blank"><?php echo $enlace; ?></a>
        </div>

        <p class="info-text">
            <i class='bx bx-copy'></i> Copia este enlace y pégalo en tu navegador
        </p>

        <div class="btn-group">
            <a href="<?php echo $enlace; ?>" class="btn btn-success" target="_blank">
                <i class='bx bx-link'></i> Ir a restablecer
            </a>
            <a href="Login.html" class="btn btn-secondary">
                <i class='bx bx-arrow-back'></i> Volver al Login
            </a>
        </div>

        <div class="info-text" style="margin-top: 15px; font-size: 11px; color: #999;">
            ⚠️ Esta es una versión de desarrollo. En producción se enviaría por correo.
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
?>