<?php
// admin_panel.php - Panel Administrador (CON LOGO)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Inicializar variables de estadísticas
$total_usuarios = 0;
$total_estudiantes = 0;
$total_docentes = 0;
$total_carreras = 0;
$total_grupos = 0;
$documentos_pendientes = 0;

if (!$conn->connect_error) {
    $total_usuarios = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'] ?? 0;
    $total_estudiantes = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE Rol IN ('Estudiante', 'NuevoIngreso')")->fetch_assoc()['total'] ?? 0;
    $total_docentes = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE Rol = 'Docente'")->fetch_assoc()['total'] ?? 0;
    $total_carreras = $conn->query("SELECT COUNT(*) as total FROM carreras")->fetch_assoc()['total'] ?? 0;
    $total_grupos = $conn->query("SELECT COUNT(*) as total FROM grupos")->fetch_assoc()['total'] ?? 0;
    $documentos_pendientes = $conn->query("SELECT COUNT(*) as total FROM documentos_alumnos WHERE estado = 'Pendiente'")->fetch_assoc()['total'] ?? 0;
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrador · ITSZ</title>
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

        /* ===== HEADER INSTITUCIONAL CON LOGO ===== */
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
            max-width: 1100px;
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
            letter-spacing: -0.3px;
        }

        .header-itsz .logo h1 small {
            display: block;
            font-weight: 400;
            font-size: 0.65rem;
            color: rgba(255,255,255,0.7);
            letter-spacing: 0.3px;
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

        /* ===== CONTENEDOR PRINCIPAL ===== */
        .main-content {
            flex: 1;
            padding: 24px 20px;
        }

        .container { max-width: 1100px; margin: 0 auto; }

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
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
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

        /* ===== ESTADÍSTICAS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--gris);
            padding: 14px 16px;
            border-radius: var(--radius);
            border-left: 4px solid var(--azul);
            text-align: center;
            transition: 0.3s;
        }
        .stat-card:hover { border-left-color: var(--dorado); }

        .stat-card .number {
            font-size: 22px;
            font-weight: 700;
            color: var(--azul);
        }
        .stat-card .label {
            font-size: 9px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.5;
            font-weight: 600;
            display: block;
            margin-top: 2px;
        }
        .stat-card .icon {
            font-size: 18px;
            color: var(--dorado);
            display: block;
            margin-bottom: 4px;
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

        .btn-config {
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
        .btn-config:hover { background: var(--guinda); transform: translateY(-2px); }

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

        .menu-card .badge {
            background: var(--rojo);
            color: var(--blanco);
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            margin-top: 4px;
            display: inline-block;
        }

        /* ===== FOOTER INSTITUCIONAL ===== */
        .footer-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            padding: 24px 20px;
            text-align: center;
            border-top: 4px solid var(--dorado);
            margin-top: auto;
        }

        .footer-itsz .container {
            max-width: 1100px;
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
            .stats-grid { grid-template-columns: repeat(3, 1fr); }
            .menu-grid { grid-template-columns: 1fr 1fr; }
            .bienvenida { flex-direction: column; align-items: flex-start; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .menu-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- ===== HEADER INSTITUCIONAL CON LOGO ===== -->
    <header class="header-itsz">
        <div class="container">
            <div class="logo">
                <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" 
                     onerror="this.src='uploads/logo_universidad.png'">
                <div>
                    <h1>
                        Tecnológico Superior de Zongolica
                        <small>Sistema Integral Académico</small>
                    </h1>
                </div>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
                <a href="admin_panel.php"><i class="fas fa-th-large"></i> Panel</a>
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
                    <div>
                        <h1><i class="fas fa-user-shield"></i> Panel Administrador</h1>
                        <p>Bienvenido, <strong><?php echo htmlspecialchars($username); ?></strong></p>
                    </div>
                </div>

                <div class="card-body">
                    <!-- ===== ESTADÍSTICAS ===== -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <span class="icon"><i class="fas fa-users"></i></span>
                            <span class="number"><?php echo $total_usuarios; ?></span>
                            <span class="label">Usuarios</span>
                        </div>
                        <div class="stat-card">
                            <span class="icon"><i class="fas fa-user-graduate"></i></span>
                            <span class="number"><?php echo $total_estudiantes; ?></span>
                            <span class="label">Estudiantes</span>
                        </div>
                        <div class="stat-card">
                            <span class="icon"><i class="fas fa-chalkboard-teacher"></i></span>
                            <span class="number"><?php echo $total_docentes; ?></span>
                            <span class="label">Docentes</span>
                        </div>
                        <div class="stat-card">
                            <span class="icon"><i class="fas fa-graduation-cap"></i></span>
                            <span class="number"><?php echo $total_carreras; ?></span>
                            <span class="label">Carreras</span>
                        </div>
                        <div class="stat-card">
                            <span class="icon"><i class="fas fa-layer-group"></i></span>
                            <span class="number"><?php echo $total_grupos; ?></span>
                            <span class="label">Grupos</span>
                        </div>
                        <div class="stat-card">
                            <span class="icon"><i class="fas fa-file-alt"></i></span>
                            <span class="number"><?php echo $documentos_pendientes; ?></span>
                            <span class="label">Documentos Pendientes</span>
                        </div>
                    </div>

                    <!-- ===== BIENVENIDA ===== -->
                    <div class="bienvenida">
                        <span class="texto">
                            <i class="fas fa-hand-peace" style="color: var(--dorado);"></i>
                            Hola, <strong><?php echo htmlspecialchars($username); ?></strong> ¡Bienvenido al panel de administración!
                        </span>
                        <a href="admin_configuracion.php" class="btn-config">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                    </div>

                    <!-- ===== MÓDULOS ===== -->
                    <div class="menu-grid">
                        <a href="admin_gestionar_usuarios.php" class="menu-card">
                            <i class="fas fa-users-cog"></i>
                            <h3>Gestionar Usuarios</h3>
                            <p>Administra estudiantes, docentes y admins</p>
                        </a>

                        <a href="admin_gestionar_carreras.php" class="menu-card">
                            <i class="fas fa-graduation-cap"></i>
                            <h3>Gestionar Carreras</h3>
                            <p>Crea y administra carreras</p>
                        </a>

                        <a href="admin_gestionar_materias.php" class="menu-card">
                            <i class="fas fa-book"></i>
                            <h3>Gestionar Materias</h3>
                            <p>Administra las materias por carrera</p>
                        </a>

                        <a href="admin_gestionar_grupos.php" class="menu-card">
                            <i class="fas fa-layer-group"></i>
                            <h3>Gestionar Grupos</h3>
                            <p>Crea grupos y asigna horarios</p>
                        </a>

                        <a href="admin_gestionar_periodos.php" class="menu-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>Gestionar Períodos</h3>
                            <p>Configura períodos académicos</p>
                        </a>

                        <a href="admin_calificaciones.php" class="menu-card">
                            <i class="fas fa-edit"></i>
                            <h3>Calificaciones</h3>
                            <p>Gestiona calificaciones</p>
                        </a>

                        <a href="admin_gestionar_documentos.php" class="menu-card">
                            <i class="fas fa-file-alt"></i>
                            <h3>Documentos</h3>
                            <p>Revisa documentos de nuevo ingreso</p>
                            <?php if ($documentos_pendientes > 0): ?>
                                <span class="badge"><?php echo $documentos_pendientes; ?></span>
                            <?php endif; ?>
                        </a>

                        <a href="admin_ver_solicitudes_becas.php" class="menu-card">
                            <i class="fas fa-hand-holding-heart"></i>
                            <h3>Solicitudes de Becas</h3>
                            <p>Gestiona solicitudes de becas</p>
                        </a>

                        <a href="admin_importar_exportar.php" class="menu-card">
                            <i class="fas fa-exchange-alt"></i>
                            <h3>Importar / Exportar</h3>
                            <p>Importa y exporta datos del sistema</p>
                        </a>

                        <a href="admin_dashboard.php" class="menu-card">
                            <i class="fas fa-chart-line"></i>
                            <h3>Dashboard</h3>
                            <p>Estadísticas del sistema</p>
                        </a>

                        <a href="logs_sistema.php" class="menu-card">
                            <i class="fas fa-history"></i>
                            <h3>Logs del Sistema</h3>
                            <p>Registro de actividades</p>
                        </a>

                        <a href="admin_becas.php" class="menu-card">
                            <i class="fas fa-award"></i>
                            <h3>Becas</h3>
                            <p>Gestiona becas disponibles</p>
                        </a>

                        <a href="admin_calendario.php" class="menu-card">
                            <i class="fas fa-calendar-check"></i>
                            <h3>Calendario</h3>
                            <p>Eventos académicos</p>
                        </a>

                        <a href="admin_libros.php" class="menu-card">
                            <i class="fas fa-book-open"></i>
                            <h3>Biblioteca</h3>
                            <p>Gestiona libros</p>
                        </a>

                        <a href="admin_cuadro_honor.php" class="menu-card">
                            <i class="fas fa-trophy"></i>
                            <h3>Cuadro de Honor</h3>
                            <p>Mejores promedios</p>
                        </a>

                        <a href="mensajes.php" class="menu-card">
                            <i class="fas fa-envelope"></i>
                            <h3>Mensajería</h3>
                            <p>Envía y recibe mensajes</p>
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