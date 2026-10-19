<?php
// admin_gestionar_docentes.php - Gestión de especialidades de docentes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

$mensaje = "";
$mensaje_tipo = "";
$docente_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ============================================================
// OBTENER DATOS DEL DOCENTE
// ============================================================
$docente = null;
if ($docente_id > 0) {
    $stmt = $conn->prepare("SELECT Id, Nombre, Correo, especialidad FROM usuarios WHERE Id = ? AND Rol = 'Docente'");
    $stmt->bind_param("i", $docente_id);
    $stmt->execute();
    $docente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$docente) {
        die("Docente no encontrado.");
    }
}

// ============================================================
// OBTENER TODAS LAS CARRERAS
// ============================================================
$carreras = $conn->query("SELECT id, nombre, clave FROM carreras ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// ============================================================
// OBTENER ESPECIALIDADES ACTUALES DEL DOCENTE
// ============================================================
$especialidades_actuales = [];
if ($docente_id > 0) {
    $stmt = $conn->prepare("SELECT carrera_id, tipo FROM docentes_especialidades WHERE docente_id = ?");
    $stmt->bind_param("i", $docente_id);
    $stmt->execute();
    $especialidades_actuales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// ============================================================
// PROCESAR GUARDADO DE ESPECIALIDADES
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_especialidades'])) {
    $docente_id_post = (int)$_POST['docente_id'];
    $especialidades = $_POST['especialidades'] ?? [];
    $tipos = $_POST['tipo'] ?? [];

    $conn->begin_transaction();

    try {
        // Eliminar especialidades existentes
        $stmt = $conn->prepare("DELETE FROM docentes_especialidades WHERE docente_id = ?");
        $stmt->bind_param("i", $docente_id_post);
        $stmt->execute();
        $stmt->close();

        // Insertar nuevas especialidades
        $insertados = 0;
        foreach ($especialidades as $index => $carrera_id) {
            if (empty($carrera_id)) continue;
            
            $carrera_id = (int)$carrera_id;
            $tipo = $tipos[$index] ?? 'Tronco Común';
            
            $stmt = $conn->prepare("INSERT INTO docentes_especialidades (docente_id, carrera_id, tipo) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $docente_id_post, $carrera_id, $tipo);
            if ($stmt->execute()) {
                $insertados++;
            }
            $stmt->close();
        }

        // Actualizar especialidad resumida en usuarios
        $nombres_carreras = [];
        foreach ($especialidades as $carrera_id) {
            if (empty($carrera_id)) continue;
            foreach ($carreras as $c) {
                if ($c['id'] == $carrera_id) {
                    $nombres_carreras[] = $c['nombre'];
                    break;
                }
            }
        }
        $especialidad_resumen = implode(', ', array_unique($nombres_carreras));

        $stmt = $conn->prepare("UPDATE usuarios SET especialidad = ? WHERE Id = ?");
        $stmt->bind_param("si", $especialidad_resumen, $docente_id_post);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        $mensaje = "✅ Especialidades actualizadas correctamente. ($insertados carreras asignadas)";
        $mensaje_tipo = "success";

        header("Location: admin_gestionar_docentes.php?id=" . $docente_id_post . "&success=" . urlencode($mensaje));
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $mensaje = "❌ Error al guardar: " . $e->getMessage();
        $mensaje_tipo = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Docentes · ITSZ</title>
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
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container { max-width: 1000px; margin: 0 auto; }

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

        .card-header .btn-volver {
            padding: 8px 20px;
            background: rgba(255,255,255,0.15);
            color: var(--blanco);
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s;
        }
        .card-header .btn-volver:hover { background: rgba(255,255,255,0.25); }

        .card-body { padding: 24px 30px; }

        .mensaje {
            padding: 12px 18px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid transparent;
        }
        .mensaje.success { background: #d4edda; color: #155724; border-left-color: var(--verde-exito); }
        .mensaje.error { background: #f8d7da; color: #721c24; border-left-color: var(--rojo); }

        /* ===== INFO DOCENTE ===== */
        .info-docente {
            background: var(--gris);
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            border-left: 4px solid var(--dorado);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 30px;
        }

        .info-docente .item .label {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 600;
            display: block;
        }

        .info-docente .item .value {
            font-weight: 700;
            color: var(--azul);
            font-size: 15px;
            margin-top: 2px;
            display: block;
        }

        /* ===== TÍTULOS DE SECCIÓN ===== */
        .section-title {
            color: var(--azul);
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i { color: var(--dorado); }

        /* ===== ESPECIALIDADES ACTUALES ===== */
        .especialidades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .especialidad-card {
            background: var(--gris);
            border-radius: var(--radius);
            padding: 14px 18px;
            border-left: 4px solid var(--azul);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .especialidad-card .carrera {
            font-weight: 600;
            color: var(--azul);
            font-size: 13px;
            flex: 1;
        }

        .especialidad-card .tipo {
            padding: 3px 12px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .tipo-especialidad { background: #fde8e8; color: #721c24; }
        .tipo-tronco { background: #cce5ff; color: #004085; }
        .tipo-ambas { background: #d4edda; color: #155724; }

        /* ===== FORMULARIO ===== */
        .form-especialidades {
            background: #e8f0fe;
            border-radius: var(--radius);
            padding: 20px;
            margin-top: 16px;
            border-left: 4px solid var(--dorado);
        }

        .input-group {
            margin-bottom: 14px;
        }

        .input-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 4px;
            font-size: 13px;
        }

        .input-group label i {
            color: var(--dorado);
            margin-right: 4px;
        }

        .input-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 14px;
            background: var(--blanco);
            font-family: 'Inter', sans-serif;
            transition: 0.3s;
        }

        .input-group select:focus {
            border-color: var(--azul);
            outline: none;
            box-shadow: 0 0 0 3px rgba(27, 57, 106, 0.08);
        }

        .especialidad-row {
            background: var(--blanco);
            padding: 14px;
            border-radius: var(--radius);
            margin-bottom: 10px;
            border: 1px solid var(--gris-oscuro);
            position: relative;
        }

        .especialidad-row .btn-eliminar-row {
            position: absolute;
            top: 8px;
            right: 8px;
            background: var(--rojo);
            color: var(--blanco);
            border: none;
            border-radius: 50%;
            width: 26px;
            height: 26px;
            cursor: pointer;
            font-size: 12px;
            transition: 0.3s;
        }
        .especialidad-row .btn-eliminar-row:hover { background: #6b080c; transform: scale(1.1); }

        .row-2 {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 10px;
            align-items: end;
        }

        .btn-agregar-especialidad {
            padding: 10px 20px;
            background: var(--verde-exito);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 16px;
        }
        .btn-agregar-especialidad:hover { background: #219a52; transform: translateY(-2px); }

        .btn-guardar {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-guardar:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg); }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }
        .empty-state i { font-size: 40px; display: block; margin-bottom: 10px; }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .info-docente { grid-template-columns: 1fr; }
            .especialidades-grid { grid-template-columns: 1fr; }
            .row-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-chalkboard-teacher"></i> Gestionar Docente</h1>
            <a href="admin_gestionar_usuarios.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <?php if (isset($_GET['success'])): ?>
                <div class="mensaje success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($mensaje) && $mensaje_tipo === 'error'): ?>
                <div class="mensaje error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <!-- ===== INFORMACIÓN DEL DOCENTE ===== -->
            <div class="info-docente">
                <div class="item">
                    <span class="label">Nombre</span>
                    <span class="value"><i class="fas fa-user"></i> <?php echo htmlspecialchars($docente['Nombre'] ?? 'No seleccionado'); ?></span>
                </div>
                <div class="item">
                    <span class="label">Correo</span>
                    <span class="value"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($docente['Correo'] ?? '—'); ?></span>
                </div>
                <div class="item">
                    <span class="label">Especialidades</span>
                    <span class="value">
                        <?php if (!empty($docente['especialidad'])): ?>
                            <?php echo htmlspecialchars($docente['especialidad']); ?>
                        <?php else: ?>
                            <span style="color: #999;">Sin asignar</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="item">
                    <span class="label">Carreras asignadas</span>
                    <span class="value"><?php echo count($especialidades_actuales); ?></span>
                </div>
            </div>

            <!-- ===== ESPECIALIDADES ACTUALES ===== -->
            <h3 class="section-title">
                <i class="fas fa-list"></i> Carreras asignadas
            </h3>

            <?php if (count($especialidades_actuales) > 0): ?>
                <div class="especialidades-grid">
                    <?php foreach ($especialidades_actuales as $esp):
                        $carrera_nombre = '';
                        foreach ($carreras as $c) {
                            if ($c['id'] == $esp['carrera_id']) {
                                $carrera_nombre = $c['nombre'];
                                break;
                            }
                        }
                        $clase_tipo = $esp['tipo'] === 'Especialidad' ? 'tipo-especialidad' : 
                                      ($esp['tipo'] === 'Ambas' ? 'tipo-ambas' : 'tipo-tronco');
                    ?>
                        <div class="especialidad-card">
                            <span class="carrera"><?php echo htmlspecialchars($carrera_nombre); ?></span>
                            <span class="tipo <?php echo $clase_tipo; ?>"><?php echo $esp['tipo']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <p>Este docente no tiene carreras asignadas.</p>
                </div>
            <?php endif; ?>

            <!-- ===== FORMULARIO PARA ASIGNAR ESPECIALIDADES ===== -->
            <div class="form-especialidades">
                <h3 class="section-title">
                    <i class="fas fa-plus-circle"></i> Asignar especialidades
                </h3>
                <p style="font-size: 12px; color: var(--texto-oscuro); opacity: 0.7; margin-bottom: 14px;">
                    Selecciona las carreras en las que el docente puede impartir clases y el tipo de especialidad.
                </p>

                <form method="POST" id="formEspecialidades">
                    <input type="hidden" name="docente_id" value="<?php echo $docente_id; ?>">

                    <div id="especialidades-container">
                        <?php 
                        // Mostrar las especialidades actuales en el formulario
                        if (count($especialidades_actuales) > 0):
                            foreach ($especialidades_actuales as $index => $esp):
                        ?>
                            <div class="especialidad-row">
                                <?php if ($index > 0): ?>
                                    <button type="button" class="btn-eliminar-row" onclick="eliminarRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                <?php endif; ?>
                                <div class="row-2">
                                    <div class="input-group" style="margin-bottom: 0;">
                                        <label><i class="fas fa-graduation-cap"></i> Carrera</label>
                                        <select name="especialidades[]" required>
                                            <option value="">Selecciona una carrera...</option>
                                            <?php foreach ($carreras as $c): ?>
                                                <option value="<?php echo $c['id']; ?>" 
                                                    <?php echo ($c['id'] == $esp['carrera_id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo htmlspecialchars($c['clave']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-group" style="margin-bottom: 0;">
                                        <label><i class="fas fa-tag"></i> Tipo</label>
                                        <select name="tipo[]" required>
                                            <option value="Tronco Común" <?php echo ($esp['tipo'] == 'Tronco Común') ? 'selected' : ''; ?>>Tronco Común</option>
                                            <option value="Especialidad" <?php echo ($esp['tipo'] == 'Especialidad') ? 'selected' : ''; ?>>Especialidad</option>
                                            <option value="Ambas" <?php echo ($esp['tipo'] == 'Ambas') ? 'selected' : ''; ?>>Ambas</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endforeach;
                        else:
                        ?>
                            <div class="especialidad-row">
                                <div class="row-2">
                                    <div class="input-group" style="margin-bottom: 0;">
                                        <label><i class="fas fa-graduation-cap"></i> Carrera</label>
                                        <select name="especialidades[]" required>
                                            <option value="">Selecciona una carrera...</option>
                                            <?php foreach ($carreras as $c): ?>
                                                <option value="<?php echo $c['id']; ?>">
                                                    <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo htmlspecialchars($c['clave']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="input-group" style="margin-bottom: 0;">
                                        <label><i class="fas fa-tag"></i> Tipo</label>
                                        <select name="tipo[]" required>
                                            <option value="Tronco Común">Tronco Común</option>
                                            <option value="Especialidad">Especialidad</option>
                                            <option value="Ambas">Ambas</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn-agregar-especialidad" onclick="agregarEspecialidad()">
                        <i class="fas fa-plus"></i> Agregar otra carrera
                    </button>

                    <button type="submit" name="guardar_especialidades" class="btn-guardar">
                        <i class="fas fa-save"></i> Guardar Especialidades
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Template para nueva fila
    function agregarEspecialidad() {
        const container = document.getElementById('especialidades-container');
        const template = `
            <div class="especialidad-row">
                <button type="button" class="btn-eliminar-row" onclick="eliminarRow(this)">
                    <i class="fas fa-times"></i>
                </button>
                <div class="row-2">
                    <div class="input-group" style="margin-bottom: 0;">
                        <label><i class="fas fa-graduation-cap"></i> Carrera</label>
                        <select name="especialidades[]" required>
                            <option value="">Selecciona una carrera...</option>
                            <?php foreach ($carreras as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['nombre']); ?> (<?php echo htmlspecialchars($c['clave']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group" style="margin-bottom: 0;">
                        <label><i class="fas fa-tag"></i> Tipo</label>
                        <select name="tipo[]" required>
                            <option value="Tronco Común">Tronco Común</option>
                            <option value="Especialidad">Especialidad</option>
                            <option value="Ambas">Ambas</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', template);
    }

    // Eliminar fila
    function eliminarRow(btn) {
        btn.closest('.especialidad-row').remove();
    }
</script>

</body>
</html>