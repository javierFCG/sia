<?php
// estudiante_materias.php - Vista simplificada para estudiantes (SIN HORARIO)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id === 0) { die("Error: Sesión no válida."); }

if (!isset($_GET['periodo_id']) || !isset($_GET['carrera_id'])) {
    die("Datos inválidos.");
}
$periodo_id = (int)$_GET['periodo_id'];
$carrera_id = (int)$_GET['carrera_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Conexión fallida."); }

$stmt_info = $conn->prepare("SELECT c.nombre as carrera, p.nombre as periodo FROM carreras c JOIN periodos p ON 1=1 WHERE c.id = ? AND p.id = ?");
$stmt_info->bind_param("ii", $carrera_id, $periodo_id);
$stmt_info->execute();
$info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

$sql_check = "SELECT id, carrera_id FROM inscripciones WHERE usuario_id = ? AND periodo_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $user_id, $periodo_id);
$stmt_check->execute();
$res_check = $stmt_check->get_result();

$inscripcion_id = null;
if ($res_check->num_rows > 0) {
    $fila = $res_check->fetch_assoc();
    if ((int)$fila['carrera_id'] !== $carrera_id) {
        if (!isset($_GET['confirmar_cambio'])) {
            echo "<script>
                if(confirm('Ya tienes una carrera inscrita en este período. ¿Deseas cambiarla?')) {
                    window.location.href = 'estudiante_materias.php?periodo_id=$periodo_id&carrera_id=$carrera_id&confirmar_cambio=1';
                } else {
                    window.location.href = 'estudiante_carrera.php?periodo_id=$periodo_id';
                }
            </script>";
            exit();
        } else {
            $stmt_del = $conn->prepare("DELETE FROM inscripciones WHERE usuario_id = ? AND periodo_id = ?");
            $stmt_del->bind_param("ii", $user_id, $periodo_id);
            $stmt_del->execute();
            $stmt_del->close();
            $inscripcion_id = null;
        }
    } else {
        $inscripcion_id = $fila['id'];
    }
}
$stmt_check->close();

if (!$inscripcion_id) {
    $stmt_ins = $conn->prepare("INSERT INTO inscripciones (usuario_id, carrera_id, periodo_id) VALUES (?, ?, ?)");
    $stmt_ins->bind_param("iii", $user_id, $carrera_id, $periodo_id);
    $stmt_ins->execute();
    $inscripcion_id = $conn->insert_id;
    $stmt_ins->close();
}

// 🔥 MODIFICACIÓN: SOLO MOSTRAMOS CLAVE Y NOMBRE DE LA MATERIA
$sql_mat = "SELECT id, nombre, clave, creditos, semestre 
            FROM materias 
            WHERE carrera_id = ? 
            ORDER BY semestre ASC, nombre ASC";
$stmt_mat = $conn->prepare($sql_mat);
$stmt_mat->bind_param("i", $carrera_id);
$stmt_mat->execute();
$materias_disponibles = $stmt_mat->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_mat->close();

$materias_inscritas = [];
if ($inscripcion_id) {
    $res_ya = $conn->query("SELECT materia_id FROM inscripciones_detalle WHERE inscripcion_id = $inscripcion_id");
    while ($row = $res_ya->fetch_assoc()) {
        $materias_inscritas[] = $row['materia_id'];
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción - <?php echo htmlspecialchars($info['carrera']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --texto-oscuro: #333333;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 24px 30px;
            border-bottom: 4px solid var(--dorado);
        }

        .card-header h1 {
            color: var(--blanco);
            font-size: 24px;
            font-weight: 800;
        }

        .card-header h1 i {
            color: var(--dorado);
            margin-right: 12px;
        }

        .card-header p {
            color: rgba(255,255,255,0.85);
            font-size: 14px;
            margin-top: 4px;
        }

        .card-body {
            padding: 30px;
        }

        .info-carrera {
            background: var(--gris);
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            border-left: 4px solid var(--dorado);
        }

        .info-carrera strong {
            color: var(--azul);
        }

        .creditos-counter {
            background: var(--azul);
            color: var(--blanco);
            padding: 10px 24px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .creditos-counter .limit-reached {
            color: var(--dorado);
        }

        .materia-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .materia-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: var(--blanco);
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            transition: all 0.3s;
        }

        .materia-item:hover {
            border-color: var(--azul);
            box-shadow: var(--shadow);
        }

        .materia-item .materia-info {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 1;
        }

        .materia-item .materia-info .clave {
            font-weight: 700;
            color: var(--azul);
            font-size: 13px;
            background: var(--gris);
            padding: 2px 12px;
            border-radius: 20px;
            min-width: 70px;
            text-align: center;
        }

        .materia-item .materia-info .nombre {
            font-weight: 600;
            color: var(--texto-oscuro);
            font-size: 15px;
        }

        .materia-item .materia-info .creditos {
            font-size: 12px;
            color: var(--texto-oscuro);
            opacity: 0.6;
            background: var(--gris);
            padding: 2px 10px;
            border-radius: 20px;
        }

        .materia-item .materia-info .semestre {
            font-size: 12px;
            color: var(--guinda);
            font-weight: 600;
            background: #fde8e8;
            padding: 2px 10px;
            border-radius: 20px;
        }

        .materia-item input[type="checkbox"] {
            transform: scale(1.3);
            cursor: pointer;
            accent-color: var(--azul);
            margin-left: 12px;
            flex-shrink: 0;
        }

        .materia-item input[type="checkbox"]:disabled {
            accent-color: var(--gris-oscuro);
            cursor: not-allowed;
        }

        .materia-item.inscrita {
            border-color: var(--verde-exito, #27ae60);
            background: #f0fff4;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 24px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-volver {
            display: inline-block;
            margin-top: 16px;
            color: var(--azul);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid var(--azul);
            border-radius: var(--radius);
            transition: all 0.3s;
        }

        .btn-volver:hover {
            background: var(--azul);
            color: var(--blanco);
        }

        .btn-reset {
            display: inline-block;
            margin-top: 16px;
            margin-left: 10px;
            color: var(--rojo, #860A0F);
            text-decoration: none;
            font-weight: 600;
            padding: 10px 20px;
            border: 2px solid var(--rojo, #860A0F);
            border-radius: var(--radius);
            transition: all 0.3s;
        }

        .btn-reset:hover {
            background: var(--rojo, #860A0F);
            color: var(--blanco);
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--gris-oscuro);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 12px;
            display: block;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {
            .card-body { padding: 20px; }
            .card-header { padding: 18px 20px; }
            .card-header h1 { font-size: 20px; }
            .materia-item { flex-wrap: wrap; gap: 10px; }
            .materia-item .materia-info { flex-wrap: wrap; gap: 6px; }
            .materia-item input[type="checkbox"] { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-book-open"></i> Inscripción de Materias</h1>
            <p>Carrera: <strong><?php echo htmlspecialchars($info['carrera']); ?></strong> · Período: <strong><?php echo htmlspecialchars($info['periodo']); ?></strong></p>
        </div>

        <div class="card-body">
            <div class="info-carrera">
                <strong>📚 Selecciona tus materias</strong>
                <p style="margin-top: 4px; font-size: 14px; color: var(--texto-oscuro); opacity: 0.7;">
                    Elige las materias que cursarás este período. Máximo <strong>30 créditos</strong>.
                </p>
            </div>

            <div class="header-actions">
                <div class="creditos-counter">
                    Créditos Seleccionados: <span id="total-creditos">0</span> / <span id="max-creditos">30</span>
                </div>
                <?php if ($inscripcion_id): ?>
                    <a href="estudiante_reset_carrera.php?inscripcion_id=<?php echo $inscripcion_id; ?>" 
                       class="btn-reset" onclick="return confirm('¿Eliminar esta inscripción y empezar de nuevo?')">
                        <i class="fas fa-undo-alt"></i> Reiniciar
                    </a>
                <?php endif; ?>
            </div>

            <form id="form-inscripcion" action="estudiante_guardar_materias.php" method="POST">
                <input type="hidden" name="inscripcion_id" value="<?php echo $inscripcion_id; ?>">
                <input type="hidden" name="periodo_id" value="<?php echo $periodo_id; ?>">
                <input type="hidden" name="carrera_id" value="<?php echo $carrera_id; ?>">

                <div class="materia-list">
                    <?php if (count($materias_disponibles) > 0): ?>
                        <?php foreach ($materias_disponibles as $materia): 
                            $is_checked = in_array($materia['id'], $materias_inscritas);
                        ?>
                            <div class="materia-item <?php echo $is_checked ? 'inscrita' : ''; ?>">
                                <div class="materia-info">
                                    <span class="clave"><?php echo htmlspecialchars($materia['clave']); ?></span>
                                    <span class="nombre"><?php echo htmlspecialchars($materia['nombre']); ?></span>
                                    <span class="semestre">Sem. <?php echo $materia['semestre']; ?></span>
                                    <span class="creditos"><?php echo $materia['creditos']; ?> Cr.</span>
                                </div>
                                <input type="checkbox" 
                                       name="materias[]" 
                                       class="materia-checkbox"
                                       value="<?php echo $materia['id']; ?>" 
                                       data-creditos="<?php echo $materia['creditos']; ?>"
                                       <?php echo $is_checked ? 'checked' : ''; ?>>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-book"></i>
                            <p>No hay materias disponibles en esta carrera.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-save"></i> Guardar Inscripción
                </button>
            </form>
            
            <div style="margin-top: 16px;">
                <a href="estudiante_carrera.php?periodo_id=<?php echo $periodo_id; ?>&cambiar=1" class="btn-volver">
                    <i class="fas fa-arrow-left"></i> Cambiar de Carrera
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    const MAX_CREDITOS = 30;
    const checkboxes = document.querySelectorAll('.materia-checkbox');
    const totalDisplay = document.getElementById('total-creditos');
    const maxDisplay = document.getElementById('max-creditos');
    maxDisplay.textContent = MAX_CREDITOS;

    function actualizarCreditos() {
        let total = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                total += parseInt(cb.getAttribute('data-creditos'));
            }
        });
        totalDisplay.textContent = total;
        if (total > MAX_CREDITOS) {
            totalDisplay.classList.add('limit-reached');
        } else {
            totalDisplay.classList.remove('limit-reached');
        }
        return total;
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            let totalActual = actualizarCreditos();
            if (totalActual > MAX_CREDITOS) {
                alert("¡Límite excedido! No puedes inscribir más de " + MAX_CREDITOS + " créditos.");
                this.checked = false;
                actualizarCreditos();
            }
        });
    });

    actualizarCreditos();
</script>

</body>
</html>