<?php
// admin_editar_grupo.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID de grupo no válido.");
}

$grupo_id = (int)$_GET['id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener datos del grupo
$stmt = $conn->prepare("SELECT * FROM grupos WHERE id = ?");
$stmt->bind_param("i", $grupo_id);
$stmt->execute();
$grupo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$grupo) { die("Grupo no encontrado."); }

// Obtener carreras
$carreras = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_grupo'])) {
    $nombre = trim($_POST['nombre']);
    $carrera_id = (int)$_POST['carrera_id'];
    $semestre = (int)$_POST['semestre'];
    $turno = $_POST['turno'];
    $capacidad = (int)$_POST['capacidad'];
    
    $stmt = $conn->prepare("UPDATE grupos SET nombre = ?, carrera_id = ?, semestre = ?, turno = ?, capacidad = ? WHERE id = ?");
    $stmt->bind_param("siisii", $nombre, $carrera_id, $semestre, $turno, $capacidad, $grupo_id);
    $stmt->execute();
    $stmt->close();
    
    header("Location: admin_gestionar_grupos.php?success=actualizado");
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Grupo - ITSZ</title>
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
            max-width: 600px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .main-container .logo {
            text-align: center;
            margin-bottom: 20px;
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

        .grupo-id {
            text-align: center;
            font-size: 13px;
            color: var(--gris-oscuro);
            margin-bottom: 20px;
            background: var(--gris);
            padding: 8px;
            border-radius: var(--radius);
        }
        .grupo-id strong {
            color: var(--azul);
        }

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
        .form-group .hint {
            font-size: 11px;
            color: var(--gris-oscuro);
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
            <h1><i class='bx bxs-group'></i> Editar Grupo</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <!-- ID del grupo -->
        <div class="grupo-id">
            <i class='bx bxs-id-card'></i> <strong>ID:</strong> <?php echo $grupo['id']; ?> | 
            <i class='bx bxs-group'></i> <strong>Grupo:</strong> <?php echo htmlspecialchars($grupo['nombre']); ?>
        </div>

        <form method="POST">
            <!-- Nombre -->
            <div class="form-group">
                <label for="nombre"><i class='bx bxs-group'></i> Nombre del Grupo *</label>
                <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($grupo['nombre']); ?>" required>
                <div class="hint">Ejemplo: 1A, 2B, 3C...</div>
            </div>

            <!-- Carrera -->
            <div class="form-group">
                <label for="carrera_id"><i class='bx bxs-graduation'></i> Carrera *</label>
                <select name="carrera_id" id="carrera_id" required>
                    <?php foreach ($carreras as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($grupo['carrera_id'] == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Semestre -->
            <div class="form-group">
                <label for="semestre"><i class='bx bxs-calendar-star'></i> Semestre *</label>
                <input type="number" id="semestre" name="semestre" value="<?php echo $grupo['semestre']; ?>" min="1" max="12" required>
                <div class="hint">Número del semestre (1-12)</div>
            </div>

            <!-- Turno -->
            <div class="form-group">
                <label for="turno"><i class='bx bxs-time'></i> Turno *</label>
                <select name="turno" id="turno" required>
                    <option value="Matutino" <?php echo ($grupo['turno'] == 'Matutino') ? 'selected' : ''; ?>>Matutino</option>
                    <option value="Vespertino" <?php echo ($grupo['turno'] == 'Vespertino') ? 'selected' : ''; ?>>Vespertino</option>
                    <option value="Mixto" <?php echo ($grupo['turno'] == 'Mixto') ? 'selected' : ''; ?>>Mixto</option>
                </select>
            </div>

            <!-- Capacidad -->
            <div class="form-group">
                <label for="capacidad"><i class='bx bxs-user'></i> Capacidad *</label>
                <input type="number" id="capacidad" name="capacidad" value="<?php echo $grupo['capacidad']; ?>" min="1" max="50" required>
                <div class="hint">Número máximo de alumnos (1-50)</div>
            </div>

            <button type="submit" name="actualizar_grupo" class="btn-guardar">
                <i class='bx bxs-save'></i> Guardar Cambios
            </button>
        </form>

        <a href="admin_gestionar_grupos.php" class="back-link">
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