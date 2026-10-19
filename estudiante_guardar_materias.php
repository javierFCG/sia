<?php
// estudiante_guardar_materias.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

// Verificar que los datos existan
if (!isset($_POST['inscripcion_id']) || !isset($_POST['periodo_id']) || !isset($_POST['carrera_id'])) {
    die("Error: Datos incompletos.");
}

$inscripcion_id = (int)$_POST['inscripcion_id'];
$periodo_id = (int)$_POST['periodo_id'];
$carrera_id = (int)$_POST['carrera_id'];
$materias_seleccionadas = $_POST['materias'] ?? [];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$conn->begin_transaction();
$success = true;
$mensaje = "Inscripción actualizada con éxito.";

try {
    // Eliminar materias previas
    $stmt_delete = $conn->prepare("DELETE FROM inscripciones_detalle WHERE inscripcion_id = ?");
    $stmt_delete->bind_param("i", $inscripcion_id);
    $stmt_delete->execute();
    $stmt_delete->close();
    
    // Insertar nuevas materias
    if (!empty($materias_seleccionadas)) {
        $stmt_insert = $conn->prepare("INSERT INTO inscripciones_detalle (inscripcion_id, materia_id) VALUES (?, ?)");
        foreach ($materias_seleccionadas as $materia_id) {
            $materia_id_int = (int)$materia_id;
            $stmt_insert->bind_param("ii", $inscripcion_id, $materia_id_int);
            if (!$stmt_insert->execute()) {
                throw new Exception("Error al insertar materia ID: " . $materia_id);
            }
        }
        $stmt_insert->close();
    }
    
    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    $success = false;
    $mensaje = "Error: " . $e->getMessage();
}
$conn->close();

// Obtener nombre del usuario
$nombre_usuario = $_SESSION['username'] ?? 'Estudiante';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Inscripción - ITSZ</title>
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

        .result-container {
            background: var(--blanco);
            padding: 50px 40px;
            border-radius: var(--radius-lg);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            max-width: 520px;
            width: 100%;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .result-container .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .result-container .logo img {
            max-height: 70px;
            margin-bottom: 8px;
        }
        .result-container .logo h1 {
            color: var(--azul);
            font-size: 22px;
        }
        .result-container .logo p {
            color: var(--guinda);
            font-size: 13px;
            font-weight: 500;
        }

        .icon-result {
            font-size: 80px;
            margin: 15px 0;
            display: block;
        }
        .icon-result.success { color: #27ae60; }
        .icon-result.error { color: var(--rojo); }

        .result-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .result-title.success { color: #27ae60; }
        .result-title.error { color: var(--rojo); }

        .result-message {
            color: var(--gris-oscuro);
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        .result-message strong {
            color: var(--azul);
        }

        .btn-group {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        .btn {
            padding: 14px 20px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            text-align: center;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
        }
        .btn-success {
            background: #27ae60;
            color: var(--blanco);
        }
        .btn-success:hover { background: #219a52; }
        .btn-secondary {
            background: var(--gris-oscuro);
            color: var(--blanco);
        }
        .btn-secondary:hover { background: #b0b0b0; }

        .info-box {
            background: #f8f5f0;
            padding: 15px 20px;
            border-radius: var(--radius);
            margin: 15px 0 25px;
            border-left: 4px solid var(--azul);
            text-align: left;
        }
        .info-box p {
            color: var(--gris-oscuro);
            font-size: 14px;
            margin: 3px 0;
        }
        .info-box p strong {
            color: var(--azul);
        }
        .info-box .materias-count {
            color: var(--guinda);
            font-weight: 700;
            font-size: 18px;
        }

        @media (max-width: 480px) {
            .result-container { padding: 30px 20px; margin: 10px; }
            .result-container .logo h1 { font-size: 18px; }
            .icon-result { font-size: 60px; }
            .result-title { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="result-container">
        <!-- Logo -->
        <div class="logo">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <h1>Tecnológico Superior de Zongolica</h1>
            <p>Sistema Integral Académico</p>
        </div>

        <!-- Icono -->
        <span class="icon-result <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? '✅' : '❌'; ?>
        </span>

        <!-- Título -->
        <div class="result-title <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $success ? '¡Inscripción Exitosa!' : 'Error de Inscripción'; ?>
        </div>

        <!-- Mensaje -->
        <div class="result-message">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>

        <!-- Información adicional -->
        <div class="info-box">
            <p><strong>👤 Estudiante:</strong> <?php echo htmlspecialchars($nombre_usuario); ?></p>
            <p><strong>📚 Materias inscritas:</strong> <span class="materias-count"><?php echo count($materias_seleccionadas); ?></span></p>
            <?php if ($success): ?>
                <p style="margin-top: 8px; color: #27ae60;">✅ Tus materias han sido guardadas correctamente.</p>
            <?php endif; ?>
        </div>

        <!-- Botones -->
        <div class="btn-group">
            <?php if ($success): ?>
                <a href="carga.php" class="btn btn-success">
                    <i class='bx bxs-file-pdf'></i> Ver mi Carga Académica
                </a>
            <?php endif; ?>
            <a href="estudiante_panel.php" class="btn btn-primary">
                <i class='bx bxs-dashboard'></i> Volver al Panel
            </a>
            <?php if (!$success): ?>
                <a href="estudiante_materias.php?periodo_id=<?php echo $periodo_id; ?>&carrera_id=<?php echo $carrera_id; ?>" 
                   class="btn btn-secondary" style="background: var(--dorado);">
                    <i class='bx bx-arrow-back'></i> Intentar de nuevo
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>