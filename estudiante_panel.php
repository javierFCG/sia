<?php
// estudiante_panel.php - Panel Estudiante (CON CARRERA DESDE INSCRIPCIÓN)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$nombre_usuario = $_SESSION['username'];
$user_id = $_SESSION['user_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// ============================================================
// OBTENER INFORMACIÓN DEL ESTUDIANTE (CON CARRERA DESDE INSCRIPCIÓN)
// ============================================================

// Primero, obtener datos básicos del usuario
$stmt = $conn->prepare("SELECT grupo, semestre, carrera_id FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

$carrera_nombre = '';
$carrera_id = 0;
$grupo = '';
$semestre = 1;

// ✅ BUSCAR LA CARRERA EN INSCRIPCIONES (la más reciente)
$stmt = $conn->prepare("
    SELECT i.carrera_id, c.nombre as carrera_nombre, u.grupo, u.semestre
    FROM inscripciones i
    JOIN carreras c ON i.carrera_id = c.id
    JOIN usuarios u ON i.usuario_id = u.Id
    WHERE i.usuario_id = ?
    ORDER BY i.fecha_inscripcion DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$inscripcion = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($inscripcion) {
    // Si tiene inscripción, usar los datos de la inscripción
    $carrera_id = $inscripcion['carrera_id'];
    $carrera_nombre = $inscripcion['carrera_nombre'];
    $grupo = $inscripcion['grupo'] ?? ($usuario['grupo'] ?? '');
    $semestre = $inscripcion['semestre'] ?? ($usuario['semestre'] ?? 1);
} else {
    // Si no tiene inscripción, usar los datos del usuario
    $grupo = $usuario['grupo'] ?? '';
    $semestre = $usuario['semestre'] ?? 1;
    
    if ($usuario['carrera_id']) {
        $stmt = $conn->prepare("SELECT nombre FROM carreras WHERE id = ?");
        $stmt->bind_param("i", $usuario['carrera_id']);
        $stmt->execute();
        $carrera = $stmt->get_result()->fetch_assoc();
        $carrera_nombre = $carrera['nombre'] ?? '';
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
    <title>Panel Estudiante · ITSZ</title>
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
            background: var(--gris);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER INSTITUCIONAL ===== */
        .header-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 12px 0;
            box-shadow: var(--shadow);
            border-bottom: 4px solid var(--dorado);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-itsz .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .header-itsz .logo {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-itsz .logo img {
            height: 50px;
            width: auto;
        }

        .header-itsz .logo h1 {
            color: var(--blanco);
            font-weight: 800;
            font-size: 1.1rem;
        }

        .header-itsz .logo h1 small {
            display: block;
            font-weight: 400;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.7);
        }

        .header-itsz nav {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-itsz nav a {
            color: var(--blanco);
            font-size: 0.85rem;
            font-weight: 500;
            padding: 6px 16px;
            border-radius: var(--radius);
            transition: all 0.3s;
            text-decoration: none;
        }

        .header-itsz nav a:hover {
            background: rgba(255, 255, 255, 0.12);
            transform: translateY(-2px);
        }

        .header-itsz nav .btn-salir {
            background: var(--rojo);
            padding: 6px 18px;
            border-radius: var(--radius);
            font-weight: 600;
        }

        .header-itsz nav .btn-salir:hover {
            background: #6b080c;
        }

        /* ===== CONTENIDO ===== */
        .main-content {
            flex: 1;
            padding: 24px 20px;
        }

        .container { max-width: 1200px; margin: 0 auto; }

        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 20px 30px;
            border-bottom: 4px solid var(--dorado);
        }

        .card-header h1 {
            color: var(--blanco);
            font-size: 22px;
            font-weight: 800;
        }
        .card-header h1 i { color: var(--dorado); margin-right: 12px; }
        .card-header p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin-top: 2px;
        }

        .card-body { padding: 24px 28px; }

        /* ===== INFO ESTUDIANTE ===== */
        .info-estudiante {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: var(--gris);
            border-radius: var(--radius);
            border-left: 4px solid var(--dorado);
        }

        .info-estudiante .item .label {
            font-size: 9px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.5;
            font-weight: 600;
            display: block;
        }

        .info-estudiante .item .value {
            font-size: 15px;
            font-weight: 700;
            color: var(--azul);
            margin-top: 2px;
        }

        .info-estudiante .item .value i {
            color: var(--dorado);
            margin-right: 6px;
            font-size: 13px;
        }

        /* ===== BIENVENIDA ===== */
        .bienvenida {
            background: #fff8e1;
            padding: 14px 18px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--dorado);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .bienvenida .texto {
            font-size: 14px;
            color: var(--texto-oscuro);
        }
        .bienvenida .texto strong { color: var(--azul); }

        .btn-cambiar-pass {
            padding: 6px 18px;
            background: var(--azul);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-cambiar-pass:hover { background: var(--guinda); transform: translateY(-2px); }

        /* ===== GRID DE MÓDULOS ===== */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 14px;
        }

        .menu-card {
            background: var(--blanco);
            padding: 18px 14px;
            border-radius: var(--radius);
            border: 1px solid var(--gris-oscuro);
            text-decoration: none;
            color: var(--texto-oscuro);
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--azul);
            transition: all 0.3s;
        }

        .menu-card:hover {
            border-color: var(--azul);
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .menu-card:hover::before {
            background: var(--dorado);
            height: 4px;
        }

        .menu-card i {
            font-size: 28px;
            color: var(--azul);
            margin-bottom: 8px;
            display: block;
            transition: all 0.3s;
        }

        .menu-card:hover i {
            color: var(--dorado);
            transform: scale(1.05);
        }

        .menu-card h3 {
            font-size: 13px;
            font-weight: 700;
            color: var(--azul);
            margin-bottom: 2px;
        }

        .menu-card p {
            font-size: 10px;
            color: var(--texto-oscuro);
            opacity: 0.6;
            margin: 0;
            line-height: 1.3;
        }

        /* ===== FOOTER ===== */
        .footer-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            padding: 24px 20px;
            text-align: center;
            border-top: 4px solid var(--dorado);
            margin-top: auto;
        }

        .footer-itsz .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .footer-itsz p {
            font-size: 13px;
            opacity: 0.85;
            margin: 4px 0;
        }

        .footer-itsz .copy {
            font-size: 11px;
            opacity: 0.6;
            margin-top: 8px;
        }

        @media (max-width: 768px) {
            .header-itsz .container { flex-direction: column; text-align: center; }
            .header-itsz .logo { flex-direction: column; }
            .header-itsz nav { justify-content: center; }
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .info-estudiante { grid-template-columns: 1fr 1fr; }
            .menu-grid { grid-template-columns: 1fr 1fr; }
            .bienvenida { flex-direction: column; align-items: flex-start; }
        }

        @media (max-width: 480px) {
            .info-estudiante { grid-template-columns: 1fr; }
            .menu-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER INSTITUCIONAL ===== -->
    <header class="header-itsz">
        <div class="container">
            <div class="logo">
                <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
                <div>
                    <h1>
                        Tecnológico Superior de Zongolica
                        <small>Sistema Integral Académico</small>
                    </h1>
                </div>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="estudiante_panel.php"><i class="fas fa-th-large"></i> Panel</a>
                <a href="mensajes.php"><i class="fas fa-envelope"></i> Mensajes</a>
                <a href="logout.php" class="btn-salir"><i class="fas fa-sign-out-alt"></i> Salir</a>
            </nav>
        </div>
    </header>

    <!-- ===== CONTENIDO PRINCIPAL ===== -->
    <main class="main-content">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <h1><i class="fas fa-user-graduate"></i> Panel del Estudiante</h1>
                    <p>Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></p>
                </div>

                <div class="card-body">
                    <!-- ===== INFORMACIÓN DEL ESTUDIANTE ===== -->
                    <div class="info-estudiante">
                        <div class="item">
                            <span class="label">Estudiante</span>
                            <span class="value"><i class="fas fa-user"></i> <?php echo htmlspecialchars($nombre_usuario); ?></span>
                        </div>
                        <div class="item">
                            <span class="label">Carrera</span>
                            <span class="value">
                                <i class="fas fa-graduation-cap"></i> 
                                <?php echo htmlspecialchars($carrera_nombre ?: 'Sin asignar'); ?>
                            </span>
                        </div>
                        <div class="item">
                            <span class="label">Grupo</span>
                            <span class="value"><i class="fas fa-users"></i> <?php echo htmlspecialchars($grupo ?: 'Sin asignar'); ?></span>
                        </div>
                        <div class="item">
                            <span class="label">Semestre</span>
                            <span class="value"><i class="fas fa-layer-group"></i> <?php echo $semestre; ?>°</span>
                        </div>
                    </div>

                    <!-- ===== BIENVENIDA + CAMBIAR CONTRASEÑA ===== -->
                    <div class="bienvenida">
                        <span class="texto">
                            <i class="fas fa-hand-peace" style="color: var(--dorado);"></i>
                            Hola, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong> ¡Bienvenido a tu panel de control!
                        </span>
                        <a href="cambiar_password.php" class="btn-cambiar-pass">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </a>
                    </div>

                    <!-- ===== MÓDULOS ===== -->
                    <div class="menu-grid">
                        <a href="estudiante_inscripcion.php" class="menu-card">
                            <i class="fas fa-edit"></i>
                            <h3>Inscripción</h3>
                            <p>Selecciona materias para el período actual</p>
                        </a>

                        <a href="carga.php" class="menu-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>Mi Carga</h3>
                            <p>Consulta y descarga tu carga académica</p>
                        </a>

                        <a href="carga_horarios.php" class="menu-card">
                            <i class="fas fa-calendar-week"></i>
                            <h3>Mi Horario</h3>
                            <p>Consulta tu horario de clases por grupo</p>
                        </a>

                        <a href="estudiante_boleta.php" class="menu-card">
                            <i class="fas fa-file-alt"></i>
                            <h3>Mi Boleta</h3>
                            <p>Consulta y descarga tus calificaciones</p>
                        </a>

                        <a href="estudiante_foto.php" class="menu-card">
                            <i class="fas fa-camera"></i>
                            <h3>Mi Foto</h3>
                            <p>Actualiza tu foto de perfil</p>
                        </a>

                        <a href="estudiante_periodos_inscritos.php" class="menu-card">
                            <i class="fas fa-history"></i>
                            <h3>Historial</h3>
                            <p>Mis inscripciones anteriores</p>
                        </a>

                        <a href="estudiante_becas.php" class="menu-card">
                            <i class="fas fa-hand-holding-heart"></i>
                            <h3>Becas</h3>
                            <p>Solicita una beca</p>
                        </a>

                        <a href="estudiante_metas.php" class="menu-card">
                            <i class="fas fa-bullseye"></i>
                            <h3>Mis Metas</h3>
                            <p>Establece tus objetivos</p>
                        </a>

                        <a href="estudiante_calendario.php" class="menu-card">
                            <i class="fas fa-calendar-check"></i>
                            <h3>Calendario</h3>
                            <p>Eventos académicos</p>
                        </a>

                        <a href="estudiante_cuadro_honor.php" class="menu-card">
                            <i class="fas fa-trophy"></i>
                            <h3>Cuadro de Honor</h3>
                            <p>Los mejores promedios</p>
                        </a>

                        <a href="estudiante_tareas.php" class="menu-card">
                            <i class="fas fa-tasks"></i>
                            <h3>Mis Tareas</h3>
                            <p>Entrega y revisa tus tareas</p>
                        </a>

                        <a href="mensajes.php" class="menu-card">
                            <i class="fas fa-envelope"></i>
                            <h3>Mensajería</h3>
                            <p>Bandeja de mensajes</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ===== FOOTER INSTITUCIONAL ===== -->
    <footer class="footer-itsz">
        <div class="container">
            <p>
                <strong>Tecnológico Superior de Zongolica</strong><br>
                Carretera a la Compañía S/N, Zongolica, Veracruz
            </p>
            <p class="copy">
                &copy; <?php echo date('Y'); ?> Tecnológico Superior de Zongolica - Todos los derechos reservados
            </p>
        </div>
    </footer>

</body>
</html>