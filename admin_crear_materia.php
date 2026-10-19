<?php
// admin_crear_materia.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['carrera_id']) || !is_numeric($_GET['carrera_id'])) {
    die("ID de Carrera no especificado.");
}
$carrera_id = (int)$_GET['carrera_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt = $conn->prepare("SELECT nombre FROM carreras WHERE id = ?");
$stmt->bind_param("i", $carrera_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) { die("Carrera no encontrada."); }
$carrera_info = $result->fetch_assoc();
$nombre_carrera = $carrera_info['nombre'];
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Materia - ITSZ</title>
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
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; padding: 20px;
        }

        .main-container {
            background: var(--blanco);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 700px;
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
            font-size: 22px;
            display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .main-container .logo h1 i { color: var(--guinda); }
        .main-container .logo p {
            color: var(--guinda);
            font-size: 13px;
            font-weight: 500;
        }

        .carrera-badge {
            display: inline-block;
            background: #eaf2f8;
            color: var(--azul);
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
            border: 1px solid #d6eaf8;
        }
        .carrera-badge i { color: var(--guinda); }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 10px;
        }
        .input-group {
            margin-bottom: 5px;
        }
        .input-group.full-width { grid-column: span 2; }
        .input-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: var(--azul);
            font-size: 14px;
        }
        .input-group label i { color: var(--guinda); margin-right: 5px; }
        .input-group input, .input-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 14px;
            transition: all 0.3s;
            box-sizing: border-box;
            background: var(--gris);
            color: #333;
            font-family: inherit;
        }
        .input-group input:focus, .input-group textarea:focus {
            border-color: var(--azul);
            outline: none;
            background: var(--blanco);
            box-shadow: 0 0 0 3px rgba(27,57,106,0.1);
        }
        .input-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .input-group .hint {
            font-size: 11px;
            color: var(--gris-oscuro);
            margin-top: 3px;
        }

        .btn-submit {
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
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-submit:hover {
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
            color: var(--azul);
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

        @media (max-width: 768px) {
            .main-container { padding: 25px; margin: 10px; }
            .main-container .logo h1 { font-size: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .input-group.full-width { grid-column: span 1; }
        }
        @media (max-width: 480px) {
            .main-container { padding: 20px; }
            .main-container .logo h1 { font-size: 18px; }
            .main-container .logo img { max-height: 50px; }
            .carrera-badge { font-size: 12px; padding: 6px 12px; }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Logo -->
        <div class="logo">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <h1><i class='bx bxs-folder-plus'></i> Crear Nueva Materia</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <!-- Badge de carrera -->
        <div style="text-align: center;">
            <span class="carrera-badge">
                <i class='bx bxs-graduation'></i> Carrera: <?php echo htmlspecialchars($nombre_carrera); ?>
            </span>
        </div>

        <form action="admin_guardar_materia.php" method="POST">
            <input type="hidden" name="carrera_id" value="<?php echo $carrera_id; ?>">

            <div class="form-grid">
                <!-- Nombre -->
                <div class="input-group full-width">
                    <label for="nombre"><i class='bx bxs-book'></i> Nombre de la Materia *</label>
                    <input type="text" id="nombre" name="nombre" required placeholder="Ej: Cálculo Diferencial">
                </div>

                <!-- Clave -->
                <div class="input-group">
                    <label for="clave"><i class='bx bxs-hash'></i> Clave *</label>
                    <input type="text" id="clave" name="clave" required placeholder="Ej: MAT-101">
                </div>

                <!-- Semestre -->
                <div class="input-group">
                    <label for="semestre"><i class='bx bxs-calendar-star'></i> Semestre *</label>
                    <input type="number" id="semestre" name="semestre" required min="1" max="12" value="1">
                </div>

                <!-- Créditos -->
                <div class="input-group">
                    <label for="creditos"><i class='bx bxs-credit-card'></i> Créditos *</label>
                    <input type="number" id="creditos" name="creditos" required min="1" max="15" placeholder="Ej: 5">
                </div>

                <!-- Horario -->
                <div class="input-group full-width">
                    <label for="horario"><i class='bx bxs-time'></i> Horario Sugerido</label>
                    <input type="text" id="horario" name="horario" placeholder="Ej: Lunes y Miércoles 8:00 - 10:00">
                    <div class="hint">Este horario será visible en la carga académica</div>
                </div>

                <!-- Docente -->
                <div class="input-group full-width">
                    <label for="docente"><i class='bx bxs-user'></i> Docente Asignado</label>
                    <input type="text" id="docente" name="docente" placeholder="Nombre del profesor (Opcional)">
                    <div class="hint">El docente podrá gestionar calificaciones y tareas de esta materia</div>
                </div>

                <!-- Descripción -->
                <div class="input-group full-width">
                    <label for="descripcion"><i class='bx bxs-file'></i> Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3" placeholder="Detalles adicionales de la materia..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class='bx bxs-save'></i> Guardar Materia
            </button>
        </form>

        <a href="admin_gestionar_materias.php?carrera_id=<?php echo $carrera_id; ?>" class="back-link">
            <i class='bx bx-arrow-back'></i> Cancelar y Volver
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