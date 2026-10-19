<?php
// nuevo_ingreso_finalizar.php - Registro Completado (CON MODAL DISEÑADO)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'NuevoIngreso') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Verificar que todos los documentos estén subidos
$documentos_requeridos = ['Acta_Nacimiento', 'Certificado_Bachillerato', 'CURP', 'Comprobante_Domicilio', 'Fotografia', 'Ficha_Preinscripcion'];
$sql = "SELECT DISTINCT tipo_documento FROM documentos_alumnos WHERE usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$subidos = [];
while ($row = $result->fetch_assoc()) {
    $subidos[] = $row['tipo_documento'];
}
$stmt->close();

$faltantes = array_diff($documentos_requeridos, $subidos);

if (!empty($faltantes)) {
    $conn->close();
    header("Location: nuevo_ingreso_documentos.php?error=Faltan documentos por subir");
    exit();
}

// Cambiar rol de NuevoIngreso a Estudiante
$stmt = $conn->prepare("UPDATE usuarios SET Rol = 'Estudiante', validado = 0 WHERE Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

$_SESSION['role'] = 'Estudiante';
$_SESSION['en_espera'] = true;

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Completado · ITSZ</title>
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
            --shadow-xl: 0 20px 60px rgba(0,0,0,0.2);
            --radius: 12px;
            --radius-lg: 20px;
            --radius-xl: 28px;
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

        .card-exito {
            max-width: 620px;
            width: 100%;
            background: var(--blanco);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            border: 1px solid rgba(217, 154, 50, 0.15);
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 24px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 4px solid var(--dorado);
        }

        .header .logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header .logo i {
            color: var(--dorado);
            font-size: 2rem;
        }

        .header .logo h1 {
            color: var(--blanco);
            font-weight: 800;
            font-size: 1.1rem;
        }

        .header .logo h1 small {
            display: block;
            font-weight: 400;
            font-size: 0.6rem;
            color: rgba(255,255,255,0.7);
        }

        .header .status-badge {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 30px;
            padding: 4px 16px;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--blanco);
            text-transform: uppercase;
        }

        .header .status-badge i {
            color: var(--dorado);
            margin-right: 6px;
        }

        .contenido {
            padding: 40px 36px 36px;
            text-align: center;
        }

        .icono-exito {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--dorado) 0%, var(--dorado-intenso) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            box-shadow: 0 8px 30px rgba(217, 154, 50, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .icono-exito i {
            font-size: 3.5rem;
            color: var(--blanco);
        }

        .titulo {
            font-size: 2rem;
            font-weight: 800;
            color: var(--azul);
            margin-bottom: 8px;
        }

        .subtitulo {
            color: var(--texto-oscuro);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 28px;
        }

        .subtitulo strong {
            color: var(--guinda);
            font-weight: 700;
        }

        .separador {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, var(--dorado), var(--azul));
            margin: 0 auto 28px;
            border-radius: 4px;
        }

        .espera-container {
            background: #fff8e1;
            border-radius: var(--radius-lg);
            padding: 20px 24px;
            margin-bottom: 28px;
            border: 1px solid #ffeaa7;
            border-left: 4px solid var(--dorado);
            text-align: center;
        }

        .espera-container .espera-icono {
            font-size: 2.5rem;
            color: var(--dorado);
            display: block;
            margin-bottom: 10px;
            animation: spin 3s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .espera-container h3 {
            color: var(--azul);
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .espera-container p {
            color: var(--texto-oscuro);
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .pasos-container {
            background: var(--gris);
            border-radius: var(--radius-lg);
            padding: 24px 28px;
            margin-bottom: 20px;
            text-align: left;
            border: 1px solid var(--gris-oscuro);
        }

        .pasos-container .pasos-titulo {
            font-weight: 700;
            color: var(--azul);
            font-size: 0.95rem;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pasos-container .pasos-titulo i { color: var(--dorado); }

        .paso-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-size: 0.95rem;
            color: var(--texto-oscuro);
        }

        .paso-item:last-child { border-bottom: none; }

        .paso-item .check {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--verde-exito);
            color: var(--blanco);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .paso-item .texto { font-weight: 500; }
        .paso-item .texto small {
            display: block;
            font-weight: 400;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-size: 0.8rem;
            margin-top: 1px;
        }

        .footer {
            padding: 16px 36px 24px;
            text-align: center;
            border-top: 1px solid var(--gris);
        }

        .footer p {
            font-size: 0.75rem;
            color: var(--texto-oscuro);
            opacity: 0.5;
        }

        .footer p i { color: var(--dorado); margin: 0 4px; }

        /* ============================================================
           MODAL DE VALIDACIÓN (DISEÑO ELEGANTE)
           ============================================================ */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(10, 30, 61, 0.75);
            backdrop-filter: blur(6px);
            z-index: 3000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            animation: modalFadeIn 0.4s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: var(--blanco);
            border-radius: var(--radius-xl);
            max-width: 520px;
            width: 100%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.4);
            overflow: hidden;
            animation: modalSlideUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes modalSlideUp {
            from {
                opacity: 0;
                transform: translateY(60px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Header del modal */
        .modal-header {
            background: linear-gradient(135deg, var(--verde-exito) 0%, #219a52 100%);
            padding: 30px 32px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .modal-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 15s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .modal-header .icono-validado {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 3px solid rgba(255,255,255,0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            position: relative;
            z-index: 1;
            animation: pulseIcon 2s ease-in-out infinite;
        }

        @keyframes pulseIcon {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .modal-header .icono-validado i {
            font-size: 2.5rem;
            color: var(--blanco);
        }

        .modal-header h2 {
            color: var(--blanco);
            font-size: 1.6rem;
            font-weight: 800;
            position: relative;
            z-index: 1;
            margin-bottom: 4px;
        }

        .modal-header p {
            color: rgba(255,255,255,0.9);
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }

        /* Cuerpo del modal */
        .modal-body {
            padding: 28px 32px;
        }

        .modal-body .mensaje {
            text-align: center;
            color: var(--texto-oscuro);
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        /* Credenciales */
        .credenciales-box {
            background: var(--gris);
            border-radius: var(--radius-lg);
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid var(--dorado);
        }

        .credencial-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.06);
        }

        .credencial-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .credencial-item:first-child {
            padding-top: 0;
        }

        .credencial-item .icono {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .credencial-item .icono i {
            color: var(--dorado);
            font-size: 1.2rem;
        }

        .credencial-item .info {
            flex: 1;
            min-width: 0;
        }

        .credencial-item .info .label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 2px;
        }

        .credencial-item .info .valor {
            font-size: 1rem;
            font-weight: 700;
            color: var(--azul);
            word-break: break-all;
            display: block;
        }

        .credencial-item .info .valor.password {
            color: var(--guinda);
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            background: #fde8e8;
            padding: 2px 10px;
            border-radius: 6px;
            display: inline-block;
            font-size: 1.1rem;
        }

        /* Nota */
        .nota-importante {
            background: #fff8e1;
            border-radius: var(--radius);
            padding: 14px 18px;
            margin-bottom: 24px;
            border-left: 4px solid var(--dorado);
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .nota-importante i {
            color: var(--dorado);
            font-size: 1.2rem;
            margin-top: 2px;
        }

        .nota-importante p {
            font-size: 0.85rem;
            color: #856404;
            line-height: 1.5;
        }

        .nota-importante p strong {
            color: #721c24;
        }

        /* Botón */
        .btn-modal {
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
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(27, 57, 106, 0.3);
        }

        .btn-modal:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(27, 57, 106, 0.4);
        }

        /* Responsive */
        @media (max-width: 480px) {
            body { padding: 12px; }
            .header { padding: 16px 20px; flex-direction: column; align-items: flex-start; gap: 8px; }
            .header .logo h1 { font-size: 0.95rem; }
            .header .logo i { font-size: 1.6rem; }
            .header .status-badge { font-size: 0.6rem; padding: 3px 12px; }
            .contenido { padding: 28px 20px 24px; }
            .titulo { font-size: 1.5rem; }
            .icono-exito { width: 70px; height: 70px; }
            .icono-exito i { font-size: 2.5rem; }
            .pasos-container { padding: 18px 16px; }
            .paso-item { font-size: 0.85rem; padding: 8px 0; }
            .paso-item .check { width: 24px; height: 24px; font-size: 0.7rem; }
            .footer { padding: 12px 20px 16px; }
            .subtitulo { font-size: 0.9rem; }
            .espera-container { padding: 16px; }
            .espera-container .espera-icono { font-size: 2rem; }
            
            .modal-header { padding: 24px 20px 20px; }
            .modal-header h2 { font-size: 1.3rem; }
            .modal-body { padding: 20px; }
            .credencial-item .info .valor { font-size: 0.9rem; }
            .credencial-item .info .valor.password { font-size: 1rem; }
        }
    </style>
</head>
<body>

<div class="card-exito">
    <!-- ===== HEADER ===== -->
    <div class="header">
        <div class="logo">
            <i class="fas fa-graduation-cap"></i>
            <h1>
                Tecnológico Superior de Zongolica
                <small>Sistema Integral Académico</small>
            </h1>
        </div>
        <span class="status-badge">
            <i class="fas fa-clock"></i> En espera
        </span>
    </div>

    <!-- ===== CONTENIDO ===== -->
    <div class="contenido">
        <div class="icono-exito">
            <i class="fas fa-check"></i>
        </div>

        <h1 class="titulo">¡Registro Completado!</h1>
        <p class="subtitulo">
            Felicidades, has completado tu registro como estudiante del 
            <strong>Tecnológico Superior de Zongolica</strong>.
        </p>

        <div class="separador"></div>

        <!-- ===== MENSAJE DE ESPERA ===== -->
        <div class="espera-container">
            <span class="espera-icono">
                <i class="fas fa-hourglass-half"></i>
            </span>
            <h3>⏳ En espera de validación</h3>
            <p>
                Tu documentación está siendo revisada por el administrador.<br>
                <strong>No cierres esta página</strong> - recibirás una notificación cuando seas validado.
            </p>
        </div>

        <!-- ===== PRÓXIMOS PASOS ===== -->
        <div class="pasos-container">
            <div class="pasos-titulo">
                <i class="fas fa-list-check"></i> Próximos pasos
            </div>

            <div class="paso-item">
                <span class="check"><i class="fas fa-check"></i></span>
                <span class="texto">
                    Documentos enviados para revisión
                    <small>El administrador verificará tu documentación</small>
                </span>
            </div>

            <div class="paso-item">
                <span class="check"><i class="fas fa-check"></i></span>
                <span class="texto">
                    Recibirás una notificación de validación
                    <small>Te mostraremos tus credenciales institucionales</small>
                </span>
            </div>

            <div class="paso-item">
                <span class="check"><i class="fas fa-check"></i></span>
                <span class="texto">
                    Una vez aprobados, podrás realizar tu inscripción
                    <small>Selecciona tus materias para el período actual</small>
                </span>
            </div>

            <div class="paso-item">
                <span class="check"><i class="fas fa-check"></i></span>
                <span class="texto">
                    Consulta tu carga académica en el panel
                    <small>Accede a tu historial y calificaciones</small>
                </span>
            </div>
        </div>
    </div>

    <!-- ===== FOOTER ===== -->
    <div class="footer">
        <p>
            <i class="fas fa-check-circle"></i>
            Sistema Integral Académico &copy; <?php echo date('Y'); ?> · Tecnológico Superior de Zongolica
        </p>
    </div>
</div>

<!-- ============================================================
     MODAL DE VALIDACIÓN (DISEÑO ELEGANTE)
     ============================================================ -->
<div class="modal-overlay" id="modalValidacion">
    <div class="modal-content">
        <!-- Header del modal -->
        <div class="modal-header">
            <div class="icono-validado">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>🎉 ¡Has sido Validado!</h2>
            <p>Tu documentación ha sido aprobada</p>
        </div>

        <!-- Cuerpo del modal -->
        <div class="modal-body">
            <p class="mensaje">
                Estas son tus <strong>credenciales institucionales</strong>.<br>
                Guárdalas en un lugar seguro.
            </p>

            <!-- Credenciales -->
            <div class="credenciales-box">
                <div class="credencial-item">
                    <div class="icono">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="info">
                        <span class="label">Correo Institucional</span>
                        <span class="valor" id="modalCorreo">-</span>
                    </div>
                </div>

                <div class="credencial-item">
                    <div class="icono">
                        <i class="fas fa-id-card"></i>
                    </div>
                    <div class="info">
                        <span class="label">Número de Control</span>
                        <span class="valor" id="modalNumControl">-</span>
                    </div>
                </div>

                <div class="credencial-item">
                    <div class="icono">
                        <i class="fas fa-key"></i>
                    </div>
                    <div class="info">
                        <span class="label">Contraseña Temporal</span>
                        <span class="valor password" id="modalPassword">TecNM2025</span>
                    </div>
                </div>
            </div>

            <!-- Nota importante -->
            <div class="nota-importante">
                <i class="fas fa-exclamation-triangle"></i>
                <p>
                    <strong>Importante:</strong> Te recomendamos cambiar tu contraseña
                    al iniciar sesión por primera vez. La contraseña temporal es válida
                    solo para tu primer acceso.
                </p>
            </div>

            <!-- Botón -->
            <a href="Login.html" class="btn-modal">
                <i class="fas fa-sign-in-alt"></i> Ir a Iniciar Sesión
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     SCRIPT PARA DETECTAR VALIDACIÓN
     ============================================================ -->
<script>
    let modalMostrado = false;
    let intentos = 0;

    function verificarValidacion() {
        intentos++;
        if (intentos > 60) { // 5 minutos máximo
            clearInterval(intervaloValidacion);
            return;
        }

        fetch('ajax_validar_estado.php')
            .then(response => response.json())
            .then(data => {
                if (data.validado && !modalMostrado) {
                    modalMostrado = true;
                    clearInterval(intervaloValidacion);
                    
                    // Asignar valores al modal
                    document.getElementById('modalCorreo').textContent = data.correo_institucional || 'No asignado';
                    document.getElementById('modalNumControl').textContent = data.num_control || 'No asignado';
                    
                    // Mostrar el modal
                    document.getElementById('modalValidacion').classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            })
            .catch(error => {
                console.log('Error verificando validación:', error);
            });
    }

    // Verificar cada 5 segundos
    const intervaloValidacion = setInterval(verificarValidacion, 5000);
</script>

</body>
</html>