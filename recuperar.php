<?php
// recuperar.php - CON DISEÑO ITSZ
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - ITSZ</title>
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
        .container .subtitle {
            color: var(--gris-oscuro);
            font-size: 14px;
            text-align: center;
            margin-bottom: 25px;
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
        .mensaje.success {
            display: block;
            background: #D4EDDA;
            color: #155724;
            border: 1px solid #C3E6CB;
        }
        .mensaje.error {
            display: block;
            background: #F8D7DA;
            color: #721C24;
            border: 1px solid #F5C6CB;
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
            <h1>Recuperar Contraseña</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <p class="subtitle">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>

        <div id="mensaje" class="mensaje"></div>

        <form id="formRecuperar" action="recuperar_procesar.php" method="POST">
            <div class="input-box">
                <input type="email" name="email" placeholder="Correo Electrónico" required>
                <i class="bx bxs-envelope"></i>
            </div>
            <button type="submit" class="btn">Enviar Enlace de Recuperación</button>
        </form>

        <a href="Login.html" class="back-link">⬅️ Volver al Login</a>
    </div>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const mensaje = document.getElementById('mensaje');

        if (urlParams.has('success')) {
            mensaje.className = 'mensaje success';
            mensaje.innerHTML = '✅ Se ha enviado un enlace de recuperación a tu correo. Revisa tu bandeja de entrada.';
        } else if (urlParams.has('error')) {
            mensaje.className = 'mensaje error';
            mensaje.textContent = '❌ ' + urlParams.get('error');
        }
    </script>
</body>
</html>