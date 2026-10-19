<?php
// admin_editar_periodo.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID no válido.");
}

$periodo_id = (int)$_GET['id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("SELECT nombre, fecha_inicio, fecha_fin, estado FROM periodos WHERE id = ?");
$stmt->bind_param("i", $periodo_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) { die("Período no encontrado."); }
$periodo = $result->fetch_assoc();
$stmt->close();
$conn->close();

$estados = ['Activo', 'Inactivo', 'Cerrado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Período - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 25px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', 'Poppins', sans-serif; }
        body {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .main-container {
            background: var(--blanco);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 550px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .main-container .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .main-container .logo img {
            max-height: 70px;
            margin-bottom: 8px;
        }
        .main-container .logo h1 {
            color: var(--azul);
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .main-container .logo h1 i { color: var(--guinda); }
        .main-container .logo p {
            color: var(--guinda);
            font-size: 13px;
            font-weight: 500;
        }

        .periodo-id {
            text-align: center;
            font-size: 13px;
            color: var(--gris-oscuro);
            margin-bottom: 20px;
            background: var(--gris);
            padding: 8px;
            border-radius: var(--radius);
        }
        .periodo-id strong {
            color: var(--azul);
        }
        .periodo-id .estado-badge {
            display: inline-block;
            padding: 3px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .estado-badge.activo { background: #d4edda; color: #155724; }
        .estado-badge.inactivo { background: #fff3cd; color: #856404; }
        .estado-badge.cerrado { background: #f8d7da; color: #721c24; }

        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--azul);
            font-size: 14px;
        }
        .form-group label i {
            color: var(--guinda);
            margin-right: 5px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
            background: var(--gris);
            color: #333;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: var(--azul);
            outline: none;
            background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27,57,106,0.1);
        }
        .form-group select {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%231B396A' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
        }
        .form-group .current-value {
            font-size: 12px;
            color: var(--guinda);
            font-weight: 600;
            margin-top: 3px;
        }

        .btn-guardar {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-guardar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .back-link {
            display: block;
            margin-top: 20px;
            color: var(--gris-oscuro);
            text-decoration: none;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s;
            font-size: 14px;
        }
        .back-link:hover {
            color: var(--rojo);
        }

        .footer-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            padding: 20px;
            text-align: center;
            margin-top: 40px;
        }
        .footer-itsz .container { max-width: 1200px; margin: 0 auto; }
        .footer-itsz .social-links { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin: 10px 0; }
        .footer-itsz .social-links a { color: var(--blanco); font-size: 18px; transition: all 0.3s; }
        .footer-itsz .social-links a:hover { color: var(--dorado); transform: translateY(-3px); }
        .footer-itsz hr { border-color: rgba(255,255,255,0.15); margin: 10px 0; }
        .footer-itsz .copy { font-size: 11px; opacity: 0.8; }

        @media (max-width: 480px) {
            .main-container { padding: 25px; margin: 10px; }
            .main-container .logo h1 { font-size: 20px; }
            .main-container .logo img { max-height: 50px; }
            .form-group input, .form-group select { padding: 10px 12px; font-size: 13px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Logo -->
        <div class="logo">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <h1><i class='bx bxs-calendar-edit'></i> Editar Período</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <!-- ID y estado actual -->
        <div class="periodo-id">
            <i class='bx bxs-id-card'></i> <strong>ID:</strong> <?php echo $periodo_id; ?> | 
            <i class='bx bxs-calendar'></i> <strong>Estado actual:</strong>
            <span class="estado-badge <?php echo strtolower($periodo['estado']); ?>">
                <?php echo $periodo['estado']; ?>
            </span>
        </div>

        <form action="admin_actualizar_periodo.php" method="POST">
            <input type="hidden" name="id" value="<?php echo $periodo_id; ?>">

            <!-- Nombre -->
            <div class="form-group">
                <label for="nombre"><i class='bx bxs-book'></i> Nombre del Período *</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($periodo['nombre']); ?>" required>
                <div class="current-value">📌 Valor actual: <?php echo htmlspecialchars($periodo['nombre']); ?></div>
            </div>

            <!-- Fecha Inicio -->
            <div class="form-group">
                <label for="fecha_inicio"><i class='bx bxs-calendar-start'></i> Fecha de Inicio *</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="<?php echo $periodo['fecha_inicio']; ?>" required>
                <div class="current-value">📌 Valor actual: <?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></div>
            </div>

            <!-- Fecha Fin -->
            <div class="form-group">
                <label for="fecha_fin"><i class='bx bxs-calendar-end'></i> Fecha de Fin *</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="<?php echo $periodo['fecha_fin']; ?>" required>
                <div class="current-value">📌 Valor actual: <?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?></div>
            </div>

            <!-- Estado -->
            <div class="form-group">
                <label for="estado"><i class='bx bxs-toggle-left'></i> Estado *</label>
                <select id="estado" name="estado" required>
                    <?php foreach ($estados as $estado): ?>
                        <option value="<?php echo $estado; ?>" <?php echo ($periodo['estado'] === $estado) ? 'selected' : ''; ?>>
                            <?php echo $estado; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="current-value">📌 Valor actual: <?php echo $periodo['estado']; ?></div>
            </div>

            <button type="submit" class="btn-guardar">
                <i class='bx bxs-save'></i> Guardar Cambios
            </button>
        </form>

        <a href="admin_gestionar_periodos.php" class="back-link">
            <i class='bx bx-x-circle'></i> Cancelar y Volver
        </a>
    </div>

    <footer class="footer-itsz">
        <div class="container">
            <p><strong>Tecnológico Superior de Zongolica</strong><br>Carretera a la Compañía S/N, Zongolica, Veracruz</p>
            <div class="social-links">
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-twitter'></i></a>
                <a href="#"><i class='bx bxl-instagram'></i></a>
                <a href="#"><i class='bx bxl-youtube'></i></a>
            </div>
            <hr><p class="copy">&copy; <?php echo date('Y'); ?> Tecnológico Superior de Zongolica - Todos los derechos reservados</p>
        </div>
    </footer>
</body>
</html>